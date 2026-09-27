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
 * Credits mailbox ownership only in the request that first confirms the account.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class confirmation_mfa {
    /**
     * Marks the MFA email factor as passed for this session only.
     *
     * Called after successful confirmation and complete_user_login, never on ordinary login.
     *
     * @param int $result AUTH_CONFIRM_* result of the confirmation.
     * @param int $userid Confirmed user id.
     * @return bool True if the factor was credited.
     */
    public static function credit_email(int $result, int $userid): bool {
        global $USER;
        if (
            $result !== AUTH_CONFIRM_OK || !isloggedin() || isguestuser() ||
            (int)$USER->id !== $userid || $USER->auth !== 'coursereturn' ||
            !$USER->confirmed || $USER->suspended || $USER->deleted ||
            !\tool_mfa\manager::is_ready()
        ) {
            return false;
        }
        $factor = \tool_mfa\plugininfo\factor::get_factor('email');
        if (!$factor || !$factor->is_enabled() || !$factor->has_input()) {
            return false;
        }
        $factor->load_locked_state();
        if ($factor->get_state() !== \tool_mfa\plugininfo\factor::STATE_UNKNOWN) {
            return false;
        }
        // Same per-session API as Moodle's email factor. Never set the overall MFA
        // authenticated flag: core still evaluates weights and every other factor.
        return $factor->set_state(\tool_mfa\plugininfo\factor::STATE_PASS);
    }
}
