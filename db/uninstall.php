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
 * Uninstall callback.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Keeps accounts usable with core email authentication after removal.
 *
 * @return bool
 */
function xmldb_auth_coursereturn_uninstall() {
    global $DB;
    $DB->set_field('user', 'auth', 'email', ['auth' => 'coursereturn']);
    $auths = array_values(array_diff(get_enabled_auth_plugins(), ['coursereturn']));
    if (!in_array('email', $auths)) {
        $auths[] = 'email';
    }
    set_config('auth', implode(',', $auths));
    $DB->delete_records('user_preferences', ['name' => 'auth_coursereturn_pending']);
    if (get_config('core', 'registerauth') === 'coursereturn') {
        set_config('registerauth', 'email');
    }
    return true;
}
