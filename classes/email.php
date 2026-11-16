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
 * Course-specific account confirmation email.
 *
 * Builds the same link as core send_confirmation_email(), with a text that names the course
 * and says what happens when the link is opened.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email {
    /**
     * Confirmation link for an account, encoded like core does.
     *
     * @param \stdClass $user User record with username and secret.
     * @return string
     */
    public static function confirmation_link(\stdClass $user): string {
        $url = (new \moodle_url('/auth/coursereturn/confirm.php'))->out(false);
        // Encode trailing dots too, so mail clients keep them in the link (as core does).
        $username = str_replace('.', '%2E', urlencode($user->username));
        return $url . '?data=' . $user->secret . '/' . $username;
    }

    /**
     * Send the confirmation email for an account created from a course page.
     *
     * @param \stdClass $user New user record (id, username, secret, email, names, timecreated).
     * @param \stdClass $course Course the user signed up from.
     * @return bool Whether the email was sent.
     */
    public static function send_confirmation(\stdClass $user, \stdClass $course): bool {
        $site = get_site();
        $data = (object)\core_user::get_name_placeholders($user);
        $data->sitename = format_string($site->fullname);
        $data->coursename = flow::course_name($course);
        $data->link = self::confirmation_link($user);
        $data->expires = userdate(
            (int)($user->timecreated ?? time()) + flow::confirmation_ttl(),
            get_string('strftimedatetime', 'langconfig')
        );
        $data->admin = generate_email_signoff();

        $key = get_config('auth_coursereturn', 'autoenrol') ? 'emailconfirmation_enrol' : 'emailconfirmation_course';
        $subject = get_string('emailconfirmationsubject_course', 'auth_coursereturn', $data);
        $message = get_string($key, 'auth_coursereturn', $data);
        $messagehtml = text_to_html($message, false, false, true);

        // Sent directly, as core does, so it is not routed to another message processor.
        return email_to_user($user, \core_user::get_support_user(), $subject, $message, $messagehtml);
    }
}
