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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/login/signup_form.php');

/**
 * Core signup form (validation, CAPTCHA, policies and profile fields) plus a course notice.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signup_form extends \login_signup_form {
    /**
     * Adds a notice naming the destination course above the core fields.
     */
    public function definition() {
        global $SESSION, $DB;
        parent::definition();
        $id = flow::course_from_url($SESSION->wantsurl ?? '');
        if (!$id) {
            return;
        }
        $course = $DB->get_record('course', ['id' => $id]);
        if ($course && \core_course_category::can_view_course_info($course)) {
            $key = get_config('auth_coursereturn', 'autoenrol') ? 'signupnotice_enrol' : 'signupnotice';
            $name = format_string($course->fullname, true, ['context' => \context_course::instance($id)]);
            $element = $this->_form->createElement(
                'static',
                'coursereturnnotice',
                '',
                get_string($key, 'auth_coursereturn', $name)
            );
            $this->_form->insertElementBefore($element, 'username');
        }
    }
}
