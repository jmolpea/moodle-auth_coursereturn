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

namespace auth_coursereturn\task;

/**
 * Removes stale course destinations using the preferences API to invalidate caches.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup extends \core\task\scheduled_task {
    /**
     * Task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanup', 'auth_coursereturn');
    }

    /**
     * Deletes expired or malformed pending destinations.
     */
    public function execute() {
        global $DB;
        $records = $DB->get_recordset('user_preferences', ['name' => \auth_coursereturn\flow::PREFERENCE]);
        foreach ($records as $record) {
            \auth_coursereturn\flow::pending((int)$record->userid);
        }
        $records->close();
    }
}
