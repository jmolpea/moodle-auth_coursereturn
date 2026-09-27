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

namespace auth_coursereturn\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy API: the only personal data is a temporary user preference.
 *
 * User preferences are deleted by Moodle core when a user's data is deleted.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describes the stored preference.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        return $collection->add_user_preference(\auth_coursereturn\flow::PREFERENCE, 'privacy:pending');
    }

    /**
     * Exports the pending destination, if any.
     *
     * @param int $userid
     */
    public static function export_user_preferences(int $userid) {
        $value = get_user_preferences(\auth_coursereturn\flow::PREFERENCE, null, $userid);
        if ($value !== null) {
            writer::export_user_preference(
                'auth_coursereturn',
                \auth_coursereturn\flow::PREFERENCE,
                $value,
                get_string('privacy:pending', 'auth_coursereturn')
            );
        }
    }
}
