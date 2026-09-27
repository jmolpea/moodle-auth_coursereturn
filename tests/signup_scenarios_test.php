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
 * Tests for registration without a course, resending and native policy preservation.
 *
 * @package    auth_coursereturn
 * @category   test
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_plugin_coursereturn
 */
final class signup_scenarios_test extends \advanced_testcase {
    /**
     * Registers a new account through the plugin.
     *
     * @param bool $agreed Whether the site policy was accepted.
     * @return \stdClass
     */
    private function signup(bool $agreed): \stdClass {
        global $CFG;
        $user = (object)['username' => 'scenario' . strtolower(random_string(6)), 'password' => 'Local-Test-2026!',
            'email' => 'scenario@example.com', 'firstname' => 'Scenario', 'lastname' => 'Test',
            'auth' => 'coursereturn', 'confirmed' => 0, 'secret' => random_string(15),
            'mnethostid' => $CFG->mnet_localhost_id, 'timecreated' => time(), 'policyagreed' => (int)$agreed,
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => ''];
        get_auth_plugin('coursereturn')->user_signup($user, false);
        return $user;
    }

    /**
     * The first email is sent synchronously, without cron.
     */
    public function test_no_course_sends_email_immediately_and_confirms(): void {
        global $DB, $SESSION;
        $this->resetAfterTest();
        $SESSION->wantsurl = '';
        $sink = $this->redirectEmails();
        $user = $this->signup(false);
        $this->assertCount(1, $sink->get_messages(), 'Initial confirmation is synchronous and does not require cron.');
        $this->assertSame(AUTH_CONFIRM_OK, get_auth_plugin('coursereturn')->user_confirm($user->username, $user->secret));
        $this->assertEquals(1, $DB->get_field('user', 'confirmed', ['id' => $user->id]));
        $this->assertSame(0, flow::pending((int)$user->id));
        $sink->close();
    }

    /**
     * Resending uses the core link and keeps the course.
     */
    public function test_resend_preserves_unconfirmed_account_and_course(): void {
        global $CFG, $SESSION;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $SESSION->wantsurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $sink = $this->redirectEmails();
        $user = $this->signup(false);
        $complete = get_complete_user_data('id', $user->id);
        $this->assertTrue(send_confirmation_email($complete));
        $messages = $sink->get_messages();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('/login/confirm.php?data=', $messages[1]->body);
        $this->assertEquals($course->id, flow::course_from_url(get_user_preferences('auth_email_wantsurl', '', $user->id)));
        $sink->close();
    }

    /**
     * Policy acceptance recorded at signup survives confirmation in another browser.
     *
     * @dataProvider acceptance_provider
     * @param bool $agreed Whether the policy was accepted.
     */
    public function test_policy_acceptance_survives_signup_and_confirmation(bool $agreed): void {
        global $CFG, $DB, $SESSION;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->sitepolicyhandler = 'tool_policy';
        $policy = \tool_policy\test\helper::add_policy()->to_record();
        \tool_policy\api::make_current($policy->id);
        $this->setUser(null);
        $SESSION->wantsurl = '';
        $sink = $this->redirectEmails();
        $user = $this->signup($agreed);
        $this->assertCount(1, $sink->get_messages());
        $this->assertEquals((int)$agreed, $DB->get_field('user', 'policyagreed', ['id' => $user->id]));
        $this->assertEquals((int)$agreed, $DB->count_records(
            'tool_policy_acceptances',
            ['userid' => $user->id, 'policyversionid' => $policy->id, 'status' => 1]
        ));
        $this->assertSame(AUTH_CONFIRM_OK, get_auth_plugin('coursereturn')->user_confirm($user->username, $user->secret));
        \cache::make('core', 'presignup')->purge();
        $fresh = get_complete_user_data('id', $user->id);
        $this->assertEquals((int)$agreed, $fresh->policyagreed, 'Changing browser must not lose stored consent.');
        $sink->close();
    }

    /**
     * Acceptance scenarios.
     *
     * @return array
     */
    public static function acceptance_provider(): array {
        return ['accepted' => [true], 'not accepted' => [false]];
    }
}
