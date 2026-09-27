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
 * Completes the return to the course after login, MFA and policies.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$PAGE->set_url('/auth/coursereturn/finish.php');
$PAGE->set_context(context_system::instance());
require_login(null, false);
// Explicit defence if an administrator adds this URL to MFA exclusions.
if (\tool_mfa\manager::is_ready() && empty($SESSION->tool_mfa_authenticated)) {
    $SESSION->wantsurl = $PAGE->url->out(false);
    redirect(new moodle_url('/admin/tool/mfa/auth.php'));
}
$courseid = \auth_coursereturn\flow::pending((int)$USER->id);
if (!$courseid) {
    redirect(new moodle_url('/my/'));
}
\auth_coursereturn\flow::enrol($courseid);
unset_user_preference(\auth_coursereturn\flow::PREFERENCE, $USER->id);
unset($SESSION->wantsurl);
if (!$DB->record_exists('course', ['id' => $courseid])) {
    redirect(new moodle_url('/course/index.php'));
}
redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
