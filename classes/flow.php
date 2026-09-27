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

namespace auth_coursereturn;

/**
 * Course intent validation, confirmation routing and restricted enrolment.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flow {
    /** @var string User preference holding the confirmed course destination. */
    public const PREFERENCE = 'auth_coursereturn_pending';

    /**
     * Routes core confirmation links (used by "resend" emails) for this plugin's accounts.
     *
     * The secret is part of the lookup so the redirect cannot be used to probe usernames.
     *
     * @param \core\hook\after_config $hook
     */
    public static function route_confirmation(\core\hook\after_config $hook): void {
        global $SCRIPT, $CFG;
        if (
            $SCRIPT !== '/login/confirm.php' || CLI_SCRIPT || during_initial_install() ||
            isset($CFG->upgraderunning) || !is_enabled_auth('coursereturn')
        ) {
            return;
        }
        $data = optional_param('data', '', PARAM_RAW);
        if ($data === '') {
            $data = optional_param('p', '', PARAM_ALPHANUM) . '/' . optional_param('s', '', PARAM_RAW);
        }
        if (self::confirmation_account_exists($data)) {
            redirect(new \moodle_url('/auth/coursereturn/confirm.php', ['data' => $data]));
        }
    }

    /**
     * Checks that confirmation data belongs to an account of this plugin.
     *
     * @param string $data The "secret/username" value from a confirmation link.
     * @return bool
     */
    public static function confirmation_account_exists(string $data): bool {
        global $DB, $CFG;
        $parts = explode('/', $data, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '' || strlen($data) > 512) {
            return false;
        }
        $user = $DB->get_record('user', ['username' => $parts[1], 'auth' => 'coursereturn',
            'mnethostid' => $CFG->mnet_localhost_id], 'id, secret');
        return $user && hash_equals((string)$user->secret, $parts[0]);
    }

    /**
     * Extracts a course id from a local course or enrolment URL.
     *
     * @param string $url Candidate URL.
     * @return int Course id, or 0 if the URL is not an existing local course.
     */
    public static function course_from_url(string $url): int {
        global $CFG, $DB;
        if ($url === '' || preg_match('/[\\\\\x00-\x20]/', $url)) {
            return 0;
        }
        $base = rtrim($CFG->wwwroot, '/');
        foreach (['/course/view.php', '/enrol/index.php'] as $path) {
            $prefix = $base . $path . '?';
            if (strpos($url, $prefix) !== 0) {
                continue;
            }
            $parts = parse_url($url);
            parse_str($parts['query'] ?? '', $query);
            if (!isset($query['id']) || !is_string($query['id']) || !ctype_digit($query['id'])) {
                return 0;
            }
            $id = (int)$query['id'];
            return $id > SITEID && $DB->record_exists('course', ['id' => $id]) ? $id : 0;
        }
        return 0;
    }

    /**
     * Returns the pending course for a user, removing expired or malformed values.
     *
     * @param int $userid
     * @return int Course id or 0.
     */
    public static function pending(int $userid): int {
        $raw = get_user_preferences(self::PREFERENCE, '', $userid);
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['courseid']) || empty($data['expires']) || $data['expires'] < time()) {
            if ($raw !== '') {
                unset_user_preference(self::PREFERENCE, $userid);
            }
            return 0;
        }
        return (int)$data['courseid'];
    }

    /**
     * Sends users with a pending course to the finishing page after login.
     *
     * @param \core_user\hook\after_login_completed $hook
     */
    public static function after_login(\core_user\hook\after_login_completed $hook): void {
        global $USER, $SESSION;
        if ($USER->auth === 'coursereturn' && self::pending((int)$USER->id)) {
            $SESSION->wantsurl = (new \moodle_url('/auth/coursereturn/finish.php'))->out(false);
        }
    }

    /**
     * Enrols the current user through an open self-enrolment instance.
     *
     * Called only after require_login and MFA have completed.
     *
     * @param int $courseid
     * @return bool True if the user is actively enrolled afterwards.
     */
    public static function enrol(int $courseid): bool {
        global $USER, $DB;
        if (
            !get_config('auth_coursereturn', 'autoenrol') || !isloggedin() || isguestuser() ||
            !$USER->confirmed || $USER->suspended || $USER->deleted || self::pending((int)$USER->id) !== $courseid
        ) {
            return false;
        }
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || $courseid == SITEID || !\core_course_category::can_view_course_info($course)) {
            return false;
        }
        $context = \context_course::instance($courseid);
        // Never reactivate suspended/expired enrolments through another instance.
        $sql = 'SELECT ue.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid AND e.courseid = :courseid';
        if ($DB->record_exists_sql($sql, ['userid' => $USER->id, 'courseid' => $courseid])) {
            return false;
        }
        $plugin = enrol_get_plugin('self');
        if (!$plugin || !enrol_is_enabled('self')) {
            return false;
        }
        foreach (enrol_get_instances($courseid, true) as $instance) {
            // Core stores "no key" as NULL or ''; treat both like enrol_self does.
            if ($instance->enrol !== 'self' || !empty($instance->password) || !empty($instance->customint1)) {
                continue;
            }
            $lock = \core\lock\lock_config::get_lock_factory('auth_coursereturn')->get_lock('enrol:' . $instance->id, 10);
            if (!$lock) {
                continue;
            }
            try {
                // Re-read after acquiring the lock so limits and settings are current.
                $instance = $DB->get_record('enrol', ['id' => $instance->id], '*', MUST_EXIST);
                if (
                    !empty($instance->password) || !empty($instance->customint1) ||
                    $plugin->can_self_enrol($instance) !== true
                ) {
                    continue;
                }
                $plugin->enrol_self($instance);
                return is_enrolled($context, $USER, '', true);
            } finally {
                $lock->release();
            }
        }
        return false;
    }
}
