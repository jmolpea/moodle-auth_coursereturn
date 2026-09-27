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
 * Integration tests against real Moodle APIs and the isolated test database.
 *
 * @package    auth_coursereturn
 * @category   test
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_plugin_coursereturn
 * @covers     \auth_coursereturn\flow
 */
final class flow_test extends \advanced_testcase {
    /**
     * Creates an unconfirmed account of this plugin.
     *
     * @return \stdClass
     */
    private function account(): \stdClass {
        return $this->getDataGenerator()->create_user([
            'auth' => 'coursereturn', 'confirmed' => 0, 'secret' => random_string(15),
        ]);
    }

    /**
     * Signup sends one confirmation email immediately, pointing to the plugin endpoint.
     */
    public function test_signup_sends_core_confirmation_email_with_custom_endpoint(): void {
        global $SESSION, $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $SESSION->wantsurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $user = (object)['username' => 'signupcourse', 'password' => 'Test-Password123!', 'email' => 'signup@example.com',
            'firstname' => 'Signup', 'lastname' => 'Course', 'auth' => 'coursereturn', 'confirmed' => 0,
            'secret' => random_string(15), 'mnethostid' => $CFG->mnet_localhost_id, 'timecreated' => time(),
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => ''];
        $sink = $this->redirectEmails();
        get_auth_plugin('coursereturn')->user_signup($user, false);
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('signup@example.com', $messages[0]->to);
        $this->assertStringContainsString(
            '/auth/coursereturn/confirm.php?data=' . $user->secret . '/signupcourse',
            quoted_printable_decode($messages[0]->body)
        );
        $this->assertEquals($SESSION->wantsurl, get_user_preferences('auth_email_wantsurl', '', $user->id));
        $this->assertFalse(get_auth_plugin('coursereturn')->user_login($user->username, 'incorrect'));
        $this->assertTrue(get_auth_plugin('coursereturn')->user_login($user->username, 'Test-Password123!'));
        $sink->close();
    }

    /**
     * Only existing local course URLs are accepted as destinations.
     */
    public function test_course_url_validation(): void {
        global $CFG;
        $this->resetAfterTest();
        $c = $this->getDataGenerator()->create_course();
        foreach (['/course/view.php', '/enrol/index.php'] as $path) {
            $this->assertEquals($c->id, flow::course_from_url($CFG->wwwroot . $path . '?id=' . $c->id));
        }
        $urls = [
            'https://evil.invalid/course/view.php?id=' . $c->id,
            '//evil.invalid/',
            $CFG->wwwroot . '.evil.invalid/course/view.php?id=' . $c->id,
            $CFG->wwwroot . '/course/view.php?id[]=2',
            $CFG->wwwroot . '/course/view.php?id=-2',
            $CFG->wwwroot . '/course/view.php?id=1',
            $CFG->wwwroot . '/my/',
            $CFG->wwwroot . '/course/view.php?id=9999999',
            "javascript:alert(1)",
            $CFG->wwwroot . "/course/view.php?id=2\n",
        ];
        foreach ($urls as $url) {
            $this->assertSame(0, flow::course_from_url($url), $url);
        }
    }

    /**
     * Confirmation data is only recognised with the right secret, so usernames cannot be probed.
     */
    public function test_confirmation_account_lookup_requires_secret(): void {
        $this->resetAfterTest();
        $user = $this->account();
        $other = $this->getDataGenerator()->create_user(['auth' => 'email', 'confirmed' => 0, 'secret' => 'abc']);
        $this->assertTrue(flow::confirmation_account_exists($user->secret . '/' . $user->username));
        $this->assertFalse(flow::confirmation_account_exists('wrongsecret/' . $user->username));
        $this->assertFalse(flow::confirmation_account_exists('/' . $user->username));
        $this->assertFalse(flow::confirmation_account_exists($user->secret . '/'));
        $this->assertFalse(flow::confirmation_account_exists($user->username));
        $this->assertFalse(flow::confirmation_account_exists('abc/' . $other->username));
        $this->assertFalse(flow::confirmation_account_exists($user->secret . '/' . $user->username . str_repeat('x', 512)));
    }

    /**
     * Confirmation keeps the course, and replaying the link neither confirms nor logs in again.
     */
    public function test_confirmation_preserves_course_and_replay_cannot_login(): void {
        global $CFG, $DB, $SESSION;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->account();
        set_user_preference('auth_email_wantsurl', $CFG->wwwroot . '/course/view.php?id=' . $course->id, $user);
        $auth = get_auth_plugin('coursereturn');
        $this->assertSame(AUTH_CONFIRM_ERROR, $auth->user_confirm($user->username, 'wrong'));
        $this->assertEquals(0, $DB->get_field('user', 'confirmed', ['id' => $user->id]));
        $this->assertSame(AUTH_CONFIRM_OK, $auth->user_confirm($user->username, $user->secret));
        $this->assertEquals($course->id, flow::pending((int)$user->id));
        $this->assertFalse(get_user_preferences('auth_email_wantsurl', false, $user->id));
        $this->assertSame(AUTH_CONFIRM_ALREADY, $auth->user_confirm($user->username, $user->secret));
        $this->assertSame(AUTH_CONFIRM_ERROR, $auth->user_confirm($user->username, ''));
        // PHPUnit has no live PHP session; exercise the actual registered hook.
        // Session regeneration itself is covered by browser confirmation tests.
        $this->setUser(get_complete_user_data('id', $user->id));
        \core\di::get(\core\hook\manager::class)->dispatch(new \core_user\hook\after_login_completed());
        $this->assertEquals($CFG->wwwroot . '/auth/coursereturn/finish.php', $SESSION->wantsurl);
    }

    /**
     * Suspended, deleted, expired and foreign accounts cannot be confirmed.
     */
    public function test_confirmation_rejects_suspended_expired_deleted_and_other_auth(): void {
        global $DB;
        $this->resetAfterTest();
        $auth = get_auth_plugin('coursereturn');
        $cases = ['suspended' => 1, 'deleted' => 1, 'auth' => 'manual', 'timecreated' => time() - 8 * DAYSECS];
        foreach ($cases as $key => $value) {
            $user = $this->account();
            $DB->set_field('user', $key, $value, ['id' => $user->id]);
            $this->assertSame(AUTH_CONFIRM_ERROR, $auth->user_confirm($user->username, $user->secret), $key);
            $this->assertEquals(0, $DB->get_field('user', 'confirmed', ['id' => $user->id]));
        }
        $this->assertSame(AUTH_CONFIRM_ERROR, $auth->user_confirm('nonexistent', 'secret'));
    }

    /**
     * External destinations are discarded.
     */
    public function test_untrusted_destination_is_not_retained(): void {
        $this->resetAfterTest();
        $user = $this->account();
        set_user_preference('auth_email_wantsurl', 'https://evil.invalid/', $user);
        $this->assertSame(AUTH_CONFIRM_OK, get_auth_plugin('coursereturn')->user_confirm($user->username, $user->secret));
        $this->assertSame(0, flow::pending((int)$user->id));
    }

    /**
     * The scheduled task removes expired destinations and privacy metadata is declared.
     */
    public function test_pending_cleanup_and_privacy_metadata(): void {
        $this->resetAfterTest();
        $user = $this->account();
        set_user_preference(flow::PREFERENCE, json_encode(['courseid' => 2, 'expires' => time() - 1]), $user);
        (new \auth_coursereturn\task\cleanup())->execute();
        $this->assertFalse(get_user_preferences(flow::PREFERENCE, false, $user->id));
        $collection = new \core_privacy\local\metadata\collection('auth_coursereturn');
        $this->assertNotEmpty(\auth_coursereturn\privacy\provider::get_metadata($collection)->get_collection());
    }

    /**
     * Automatic enrolment honours every self-enrolment restriction.
     *
     * @dataProvider restriction_provider
     * @param string $restriction Scenario name.
     * @param bool $expected Whether enrolment should happen.
     */
    public function test_enrolment_restrictions(string $restriction, bool $expected): void {
        global $DB;
        $this->resetAfterTest();
        set_config('autoenrol', 1, 'auth_coursereturn');
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['auth' => 'coursereturn']);
        $this->setUser($user);
        set_user_preference(flow::PREFERENCE, json_encode(['courseid' => $course->id, 'expires' => time() + DAYSECS]), $user);
        $plugin = enrol_get_plugin('self');
        foreach (enrol_get_instances($course->id, false) as $old) {
            if ($old->enrol === 'self') {
                $plugin->delete_instance($old);
            }
        }
        $fields = ['status' => ENROL_INSTANCE_ENABLED, 'customint6' => 1, 'password' => '', 'customint1' => 0];
        switch ($restriction) {
            case 'nullkey':
                // Instances created with default settings store NULL, not ''.
                unset($fields['password']);
                break;
            case 'key':
                $fields['password'] = 'private';
                break;
            case 'groupkey':
                $fields['customint1'] = 1;
                break;
            case 'disabled':
                $fields['status'] = ENROL_INSTANCE_DISABLED;
                break;
            case 'closed':
                $fields['customint6'] = 0;
                break;
            case 'future':
                $fields['enrolstartdate'] = time() + DAYSECS;
                break;
            case 'past':
                $fields['enrolenddate'] = time() - DAYSECS;
                break;
            case 'full':
                $fields['customint3'] = 1;
                break;
            case 'cohort':
                $fields['customint5'] = $this->getDataGenerator()->create_cohort()->id;
                break;
            case 'hidden':
                $DB->set_field('course', 'visible', 0, ['id' => $course->id]);
                break;
            case 'off':
                set_config('autoenrol', 0, 'auth_coursereturn');
                break;
            case 'nointent':
                unset_user_preference(flow::PREFERENCE, $user);
                break;
            case 'unconfirmed':
                $user->confirmed = 0;
                $this->setUser($user);
                break;
            case 'suspendeduser':
                $user->suspended = 1;
                $this->setUser($user);
                break;
        }
        $id = $plugin->add_instance($course, $fields);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        if ($restriction === 'nullkey') {
            $this->assertNull($instance->password);
        }
        if ($restriction === 'full') {
            $other = $this->getDataGenerator()->create_user();
            $plugin->enrol_user($instance, $other->id, $instance->roleid);
        }
        if ($restriction === 'suspendedenrol') {
            $plugin->enrol_user($instance, $user->id, $instance->roleid, 0, 0, ENROL_USER_SUSPENDED);
        }
        $this->assertSame($expected, flow::enrol((int)$course->id), $restriction);
        if ($expected) {
            $this->assertTrue(is_enrolled(\context_course::instance($course->id), $user, '', true));
            $this->assertFalse(flow::enrol((int)$course->id), 'Repeated requests must not re-enrol.');
        } else if ($restriction !== 'suspendedenrol') {
            $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $user->id, 'enrolid' => $id]));
        }
    }

    /**
     * Restriction scenarios.
     *
     * @return array
     */
    public static function restriction_provider(): array {
        $cases = [];
        $names = ['open', 'nullkey', 'key', 'groupkey', 'disabled', 'closed', 'future', 'past', 'full', 'cohort',
            'hidden', 'off', 'nointent', 'unconfirmed', 'suspendeduser', 'suspendedenrol'];
        foreach ($names as $case) {
            $cases[$case] = [$case, in_array($case, ['open', 'nullkey'])];
        }
        return $cases;
    }

    /**
     * Accounts created by core email self-registration still confirm after switching methods.
     */
    public function test_pending_core_email_accounts_still_confirm(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['auth' => 'email', 'confirmed' => 0, 'secret' => random_string(15)]);
        $this->assertSame(AUTH_CONFIRM_OK, get_auth_plugin('coursereturn')->user_confirm($user->username, $user->secret));
    }

    /**
     * Uninstalling moves accounts to core email authentication.
     */
    public function test_uninstall_preserves_account_access(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/auth/coursereturn/db/uninstall.php');
        $user = $this->account();
        set_config('registerauth', 'coursereturn');
        set_config('auth', 'coursereturn');
        set_user_preference(flow::PREFERENCE, '{}', $user->id);
        $this->assertTrue(xmldb_auth_coursereturn_uninstall());
        $this->assertEquals('email', $DB->get_field('user', 'auth', ['id' => $user->id]));
        $this->assertEquals('email', get_config('core', 'registerauth'));
        $this->assertContains('email', get_enabled_auth_plugins());
        $this->assertFalse($DB->record_exists('user_preferences', ['name' => flow::PREFERENCE]));
    }
}
