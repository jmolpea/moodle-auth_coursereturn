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
 * Tests for the one-time MFA email factor credit.
 *
 * @package    auth_coursereturn
 * @category   test
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_coursereturn\confirmation_mfa
 */
final class confirmation_mfa_test extends \advanced_testcase {
    /**
     * Enables MFA with only the email factor and logs in a confirmed user.
     *
     * @return \stdClass
     */
    private function prepare(): \stdClass {
        $this->resetAfterTest();
        foreach (\core\component::get_plugin_list('factor') as $name => $path) {
            set_config('enabled', 0, 'factor_' . $name);
        }
        set_config('enabled', 1, 'tool_mfa');
        set_config('enabled', 1, 'factor_email');
        set_config('weight', 100, 'factor_email');
        $user = $this->getDataGenerator()->create_user(['auth' => 'coursereturn', 'confirmed' => 1]);
        $this->setUser($user);
        return $user;
    }

    /**
     * Confirmation credits only email in current session.
     */
    public function test_confirmation_credits_only_email_in_current_session(): void {
        global $SESSION;
        $user = $this->prepare();
        $this->assertTrue(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
        $this->assertSame(\tool_mfa\plugininfo\factor::STATE_PASS, $SESSION->factor_email);
        $this->assertEmpty($SESSION->tool_mfa_authenticated ?? null);
        $this->assertTrue(\tool_mfa\manager::passed_enough_factors());
        // A fresh session for the same user has no credit.
        $SESSION = new \stdClass();
        $this->assertSame(
            \tool_mfa\plugininfo\factor::STATE_UNKNOWN,
            \tool_mfa\plugininfo\factor::get_factor('email')->get_state()
        );
        $this->assertFalse(\tool_mfa\manager::passed_enough_factors());
    }

    /**
     * Replay and wrong identity do not credit.
     */
    public function test_replay_and_wrong_identity_do_not_credit(): void {
        $user = $this->prepare();
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_ALREADY, (int)$user->id));
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_ERROR, (int)$user->id));
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id + 1));
    }

    /**
     * Insufficient email weight still requires another factor.
     */
    public function test_insufficient_email_weight_still_requires_another_factor(): void {
        $user = $this->prepare();
        set_config('weight', 50, 'factor_email');
        $this->assertTrue(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
        $this->assertFalse(\tool_mfa\manager::passed_enough_factors());
    }

    /**
     * Disabled email is not credited.
     */
    public function test_disabled_email_is_not_credited(): void {
        $user = $this->prepare();
        set_config('enabled', 0, 'factor_email');
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
    }

    /**
     * Other factor state is untouched.
     */
    public function test_other_factor_state_is_untouched(): void {
        global $SESSION;
        $user = $this->prepare();
        $SESSION->factor_totp = \tool_mfa\plugininfo\factor::STATE_UNKNOWN;
        $this->assertTrue(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
        $this->assertSame(\tool_mfa\plugininfo\factor::STATE_UNKNOWN, $SESSION->factor_totp);
    }

    /**
     * Locked email is not credited.
     */
    public function test_locked_email_is_not_credited(): void {
        global $SESSION;
        $user = $this->prepare();
        $SESSION->factor_email = \tool_mfa\plugininfo\factor::STATE_LOCKED;
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
        $this->assertSame(\tool_mfa\plugininfo\factor::STATE_LOCKED, $SESSION->factor_email);
    }

    /**
     * Revoked email is not credited.
     */
    public function test_revoked_email_is_not_credited(): void {
        global $DB;
        $user = $this->prepare();
        $DB->insert_record('tool_mfa', ['userid' => $user->id, 'factor' => 'email',
            'label' => $user->email, 'revoked' => 1, 'timecreated' => time()]);
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
    }

    /**
     * Persistent lock is not credited.
     */
    public function test_persistent_lock_is_not_credited(): void {
        global $DB;
        $user = $this->prepare();
        set_config('lockout', 3, 'tool_mfa');
        $DB->insert_record('tool_mfa', ['userid' => $user->id, 'factor' => 'email',
            'label' => $user->email, 'revoked' => 0, 'lockcounter' => 100, 'timecreated' => time()]);
        $this->assertFalse(confirmation_mfa::credit_email(AUTH_CONFIRM_OK, (int)$user->id));
    }
}
