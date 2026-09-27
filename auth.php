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
     * Creates the account and sends Moodle's standard confirmation email synchronously.
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
        return parent::user_signup_with_confirmation($user, $notify, '/auth/coursereturn/confirm.php');
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
            $ttl = (int)(get_config('auth_coursereturn', 'confirmationttl') ?: 604800);
            if ($user->timecreated + $ttl < time()) {
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
