<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Authentication plugin: email self-registration with course return.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/email/auth.php');

/**
 * Email self-registration with a server-side course destination.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_coursereturn extends auth_plugin_email {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'coursereturn';
        $this->config = get_config('auth_coursereturn');
    }

    /**
     * Creates the account and sends the confirmation email synchronously.
     *
     * Sign-ups started from a course get an email that names the course; others get
     * Moodle's standard email.
     *
     * @param stdClass $user New user record.
     * @param bool $notify Print the "email sent" notice page.
     * @return bool|void
     */
    public function user_signup($user, $notify = true) {
        global $SESSION;
        // Save only a canonical course URL. Never persist arbitrary return URLs.
        $courseid = \auth_coursereturn\flow::course_from_url($SESSION->wantsurl ?? '');
        $SESSION->wantsurl = $courseid ? (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false) : '';
        if (!$courseid) {
            return parent::user_signup_with_confirmation($user, $notify, '/auth/coursereturn/confirm.php');
        }
        return $this->signup_for_course($user, $notify);
    }

    /**
     * Creates the account like auth_email does, then sends the course-specific email.
     *
     * Mirrors auth_plugin_email::user_signup_with_confirmation(); only the email and the
     * final notice differ. The course is named only if the new account may see it, so hidden
     * courses are never revealed; otherwise Moodle's standard email and notice are used.
     *
     * @param stdClass $user New user record. The password is in plain text.
     * @param bool $notify Print the "email sent" notice page.
     * @return bool|void
     */
    protected function signup_for_course(stdClass $user, bool $notify) {
        global $CFG, $SESSION, $PAGE, $OUTPUT;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        $plainpassword = $user->password;
        $user->password = hash_internal_user_password($user->password);
        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }
        if (method_exists(\core_user::class, 'create_user')) {
            // Moodle 5.3 and later.
            $user->id = \core_user::create_user($user, false, false);
            \core_user::add_password_history($user->id, $plainpassword);
        } else {
            $user->id = user_create_user($user, false, false);
            user_add_password_history($user->id, $plainpassword);
        }
        profile_save_data($user);
        set_user_preference('auth_email_wantsurl', $SESSION->wantsurl, $user);
        \core\event\user_created::create_from_userid($user->id)->trigger();

        $course = \auth_coursereturn\flow::visible_course($SESSION->wantsurl, $user);
        $sent = $course ? \auth_coursereturn\email::send_confirmation($user, $course) :
            send_confirmation_email($user, '/auth/coursereturn/confirm.php');
        if (!$sent) {
            throw new moodle_exception('auth_emailnoemail', 'auth_email');
        }
        if (!$notify) {
            return true;
        }
        $emailconfirm = get_string('emailconfirm');
        $PAGE->navbar->add($emailconfirm);
        $PAGE->set_title($emailconfirm);
        $PAGE->set_heading($PAGE->course->fullname);
        echo $OUTPUT->header();
        $message = $course ? get_string('emailconfirmsent_course', 'auth_coursereturn', (object)[
            'email' => s($user->email),
            'coursename' => \auth_coursereturn\flow::course_name($course),
        ]) : get_string('emailconfirmsent', '', s($user->email));
        notice($message, new moodle_url('/'));
    }

    /**
     * Read-only state of a confirmation link, used before asking the user to confirm.
     *
     * @param string $username Username.
     * @param string $confirmsecret Confirmation secret from the email.
     * @return string One of 'confirm', 'already', 'expired' or 'invalid'.
     */
    public function confirmation_state(string $username, string $confirmsecret): string {
        global $DB, $CFG;
        $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
        if (
            !$user || $user->deleted || $user->suspended || $user->auth !== $this->authtype ||
            $confirmsecret === '' || !hash_equals((string)$user->secret, $confirmsecret)
        ) {
            return 'invalid';
        }
        if ($user->confirmed) {
            return 'already';
        }
        return \auth_coursereturn\flow::is_expired($user) ? 'expired' : 'confirm';
    }

    /**
     * Confirms the account and stores the validated course destination.
     *
     * @param string $username Username.
     * @param string $confirmsecret Confirmation secret from the email.
     * @return int One of the AUTH_CONFIRM_* constants.
     */
    public function user_confirm($username, $confirmsecret) {
        global $DB, $CFG;
        $factory = \core\lock\lock_config::get_lock_factory('auth_coursereturn');
        $lock = $factory->get_lock('confirm:' . hash('sha256', $username), 10);
        if (!$lock) {
            throw new moodle_exception('locktimeout', 'auth_coursereturn');
        }
        try {
            $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
            // Switching self-registration methods must not break pending core email accounts.
            if ($user && $user->auth === 'email') {
                return get_auth_plugin('email')->user_confirm($username, $confirmsecret);
            }
            if (
                !$user || $user->deleted || $user->suspended || $user->auth !== $this->authtype ||
                $confirmsecret === '' || !hash_equals((string)$user->secret, (string)$confirmsecret)
            ) {
                return AUTH_CONFIRM_ERROR;
            }
            if ($user->confirmed) {
                return AUTH_CONFIRM_ALREADY;
            }
            if (\auth_coursereturn\flow::is_expired($user)) {
                return AUTH_CONFIRM_ERROR;
            }
            $courseid = \auth_coursereturn\flow::course_from_url(
                get_user_preferences('auth_email_wantsurl', '', $user->id)
            );
            $transaction = $DB->start_delegated_transaction();
            $result = parent::user_confirm($username, $confirmsecret);
            if ($result === AUTH_CONFIRM_OK && $courseid) {
                set_user_preference(
                    \auth_coursereturn\flow::PREFERENCE,
                    json_encode(['courseid' => $courseid, 'expires' => time() + DAYSECS]),
                    $user->id
                );
            }
            $transaction->allow_commit();
            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * Returns the core signup form extended with the course notice.
     *
     * @return \auth_coursereturn\signup_form
     */
    public function signup_form() {
        global $CFG;
        require_once($CFG->dirroot . '/login/signup_form.php');
        return new \auth_coursereturn\signup_form(null, null, 'post', '', ['autocomplete' => 'on']);
    }

    /**
     * Keeps an existing email self-registration CAPTCHA policy when switching methods.
     *
     * @return bool
     */
    public function is_captcha_enabled() {
        return parent::is_captcha_enabled() || get_config('auth_email', 'recaptcha');
    }
}
