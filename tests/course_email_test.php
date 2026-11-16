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
 * Tests for the course confirmation email and the read-only confirmation state.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_coursereturn\email
 * @covers     \auth_plugin_coursereturn
 * @covers     \auth_coursereturn\flow
 */
final class course_email_test extends \advanced_testcase {
    /**
     * New user record as prepared by the signup page.
     *
     * @param string $username
     * @return \stdClass
     */
    private function signup_user(string $username): \stdClass {
        global $CFG;
        return (object)['username' => $username, 'password' => 'Test-Password123!',
            'email' => $username . '@example.com', 'firstname' => 'Signup', 'lastname' => 'Course',
            'auth' => 'coursereturn', 'confirmed' => 0, 'secret' => random_string(15),
            'mnethostid' => $CFG->mnet_localhost_id, 'timecreated' => time(),
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => ''];
    }

    /**
     * Signs up from a course URL and returns the single email sent, with normalised whitespace.
     *
     * @param string $url Return URL in the session.
     * @param string $username
     * @return array [subject, body]
     */
    private function signup_email(string $url, string $username): array {
        global $SESSION;
        $SESSION->wantsurl = $url;
        $sink = $this->redirectEmails();
        $this->assertTrue(get_auth_plugin('coursereturn')->user_signup($this->signup_user($username), false));
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        return [$messages[0]->subject, preg_replace('/\s+/', ' ', quoted_printable_decode($messages[0]->body))];
    }

    /**
     * The email names the course and explains the button on the confirmation page.
     */
    public function test_email_names_course(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Applied statistics']);
        [$subject, $body] = $this->signup_email($CFG->wwwroot . '/course/view.php?id=' . $course->id, 'coursemail');
        $this->assertStringContainsString('Applied statistics', $subject);
        $this->assertStringContainsString('to access the course "Applied statistics"', $body);
        $this->assertStringContainsString('Confirm and go to the course', $body);
        $this->assertStringContainsString('/auth/coursereturn/confirm.php?data=', $body);
        $this->assertStringNotContainsString('ask to be enrolled', $body);
    }

    /**
     * With automatic enrolment on, the email says that confirming requests enrolment.
     */
    public function test_email_explains_enrolment_request(): void {
        global $CFG;
        $this->resetAfterTest();
        set_config('autoenrol', 1, 'auth_coursereturn');
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Applied statistics']);
        [, $body] = $this->signup_email($CFG->wwwroot . '/enrol/index.php?id=' . $course->id, 'enrolmail');
        $this->assertStringContainsString('By confirming, you ask to be enrolled in "Applied statistics"', $body);
    }

    /**
     * Hidden courses are never named: the standard Moodle email is sent instead.
     */
    public function test_hidden_course_gets_standard_email(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Secret course', 'visible' => 0]);
        [$subject, $body] = $this->signup_email($CFG->wwwroot . '/course/view.php?id=' . $course->id, 'hiddenmail');
        $this->assertStringNotContainsString('Secret course', $subject . $body);
        $this->assertStringContainsString('/auth/coursereturn/confirm.php?data=', $body);
    }

    /**
     * The read-only state used by the confirmation page never confirms anything.
     */
    public function test_confirmation_state(): void {
        global $DB;
        $this->resetAfterTest();
        $auth = get_auth_plugin('coursereturn');
        $user = $this->getDataGenerator()->create_user(['auth' => 'coursereturn', 'confirmed' => 0,
            'secret' => random_string(15)]);
        $this->assertSame('confirm', $auth->confirmation_state($user->username, $user->secret));
        $this->assertEquals(0, $DB->get_field('user', 'confirmed', ['id' => $user->id]));
        $this->assertSame('invalid', $auth->confirmation_state($user->username, 'wrong'));
        $this->assertSame('invalid', $auth->confirmation_state($user->username, ''));
        $this->assertSame('invalid', $auth->confirmation_state('nobody', $user->secret));
        $DB->set_field('user', 'timecreated', time() - 8 * DAYSECS, ['id' => $user->id]);
        $this->assertSame('expired', $auth->confirmation_state($user->username, $user->secret));
        $DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);
        $this->assertSame('already', $auth->confirmation_state($user->username, $user->secret));
        $DB->set_field('user', 'suspended', 1, ['id' => $user->id]);
        $this->assertSame('invalid', $auth->confirmation_state($user->username, $user->secret));
    }

    /**
     * The confirmation page shows only a hint of the email address.
     */
    public function test_masked_email(): void {
        $this->assertSame('j•••@example.com', flow::masked_email('john.smith@example.com'));
        $this->assertSame('•••', flow::masked_email('invalid'));
    }
}
