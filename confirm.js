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
 * Submits the confirmation form once.
 *
 * Deliberately standalone rather than an AMD module: confirm.php does not load the
 * theme or RequireJS, because their AJAX requests would run concurrently with the
 * session ID rotation performed by complete_user_login().
 *
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';
    var form = document.getElementById('coursereturn-confirm');
    if (!form || form.dataset.submitted) {
        return;
    }
    form.dataset.submitted = '1';
    try {
        window.history.replaceState(null, '', form.action);
    } catch (error) {
        // Optional URL cleanup must not prevent confirmation.
    }
    form.submit();
}());
