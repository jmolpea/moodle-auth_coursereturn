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
 * Link to the online configuration manual.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manual {
    /** @var string URL of the online configuration manual. */
    public const URL = 'https://claude.ai/artifact/S9nVg3PK3GW412ms6GScy9';

    /**
     * Sentence linking to the manual; the link opens in a new tab.
     *
     * @return string HTML
     */
    public static function link_html(): string {
        $link = \html_writer::link(
            self::URL,
            get_string('manuallink', 'auth_coursereturn'),
            ['target' => '_blank', 'rel' => 'noopener']
        );
        return get_string('manuallink_desc', 'auth_coursereturn', $link);
    }
}
