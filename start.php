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
 * Public entry point: remembers a course and sends anonymous visitors to the login page.
 *
 * Useful for external catalogues and courses that also permit guest viewing.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Anonymous visitors must reach this page before they have an account.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id]);
// Same error for missing and hidden courses, so the page cannot be used to enumerate them.
if (!$course || $id == SITEID || !\core_course_category::can_view_course_info($course)) {
    throw new moodle_exception('invalidcourseid');
}
$target = new moodle_url('/course/view.php', ['id' => $id]);
if (isloggedin() && !isguestuser()) {
    redirect($target);
}
$SESSION->wantsurl = $target->out(false);
redirect(get_login_url());
