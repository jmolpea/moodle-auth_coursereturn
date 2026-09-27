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
 * Admin settings.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'auth_coursereturn/info',
        '',
        new lang_string('description', 'auth_coursereturn')
    ));
    $settings->add(new admin_setting_heading(
        'auth_coursereturn/diagnostics',
        '',
        html_writer::link(
            new moodle_url('/auth/coursereturn/diagnostics.php'),
            get_string('diagnostics', 'auth_coursereturn')
        )
    ));
    $settings->add(new admin_setting_configcheckbox(
        'auth_coursereturn/autoenrol',
        new lang_string('autoenrol', 'auth_coursereturn'),
        new lang_string('autoenrol_desc', 'auth_coursereturn'),
        0
    ));
    $settings->add(new admin_setting_configduration(
        'auth_coursereturn/confirmationttl',
        new lang_string('confirmationttl', 'auth_coursereturn'),
        new lang_string('confirmationttl_desc', 'auth_coursereturn'),
        7 * DAYSECS,
        DAYSECS
    ));
    $settings->add(new admin_setting_configcheckbox(
        'auth_coursereturn/recaptcha',
        new lang_string('auth_emailrecaptcha_key', 'auth_email'),
        new lang_string('auth_emailrecaptcha', 'auth_email'),
        0
    ));
    $authplugin = get_auth_plugin('coursereturn');
    display_auth_lock_options(
        $settings,
        $authplugin->authtype,
        $authplugin->userfields,
        get_string('auth_fieldlocks_help', 'auth'),
        false,
        false
    );
}
