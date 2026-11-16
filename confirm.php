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
 * Account confirmation endpoint used in the emails sent by this plugin.
 *
 * GET never changes anything: it shows what confirming does (course, enrolment request,
 * partly hidden email address) and a button. Only the sesskey-protected POST sent by that
 * button confirms the account, so link previews and mail scanners cannot consume the link
 * and the user explicitly accepts going to (and, if enabled, enrolling in) the course.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Unconfirmed users cannot log in yet; this page is what logs them in.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');

$PAGE->set_url('/auth/coursereturn/confirm.php');
$PAGE->set_context(context_system::instance());
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

if (!is_enabled_auth('coursereturn')) {
    throw new moodle_exception('confirmationnotenabled');
}
$data = required_param('data', PARAM_RAW);
// Checks the secret too: an unknown username and a wrong secret give the same error.
if (!\auth_coursereturn\flow::confirmation_account_exists($data)) {
    throw new moodle_exception('invalidconfirmdata');
}
[$secret, $username] = explode('/', $data, 2);
// Avoid replacing somebody else's active session, including one awaiting MFA.
if (isloggedin() && !isguestuser() && $USER->username !== $username) {
    throw new moodle_exception('otheraccount', 'auth_coursereturn', new moodle_url('/login/logout.php'));
}
$finishurl = new moodle_url('/auth/coursereturn/finish.php');
$loginurl = new moodle_url('/login/index.php');
$sameuser = isloggedin() && !isguestuser() && $USER->username === $username;
/** @var auth_plugin_coursereturn $auth */
$auth = get_auth_plugin('coursereturn');

if (!data_submitted()) {
    $state = $auth->confirmation_state($username, $secret);
    if ($state === 'already') {
        if ($sameuser) {
            redirect($finishurl);
        }
        // A confirmation link is not a reusable login link.
        redirect(
            $loginurl,
            get_string('alreadyconfirmed_login', 'auth_coursereturn'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }
    if ($state === 'expired') {
        throw new moodle_exception('confirmationexpired', 'auth_coursereturn', $loginurl);
    }
    if ($state !== 'confirm') {
        throw new moodle_exception('invalidconfirmdata');
    }

    $account = $DB->get_record(
        'user',
        ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id],
        'id, email',
        MUST_EXIST
    );
    $course = \auth_coursereturn\flow::visible_course(
        get_user_preferences('auth_email_wantsurl', '', $account->id),
        $account
    );
    if (!$course) {
        $intro = get_string('confirmpage_nocourse', 'auth_coursereturn');
        $button = get_string('confirmaccount', 'auth_coursereturn');
    } else {
        $name = html_writer::tag('strong', \auth_coursereturn\flow::course_name($course));
        $key = get_config('auth_coursereturn', 'autoenrol') ? 'confirmpage_enrol' : 'confirmpage_course';
        $intro = get_string($key, 'auth_coursereturn', $name);
        $button = get_string('confirmandgo', 'auth_coursereturn');
    }

    // A minimal page without theme JavaScript or AJAX: concurrent anonymous requests could
    // overwrite the session cookie while complete_user_login() rotates the session id.
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self'; " .
        "base-uri 'none'; frame-ancestors 'none'");
    $title = get_string('confirmpage_title', 'auth_coursereturn');
    echo '<!doctype html>';
    echo html_writer::start_tag('html', ['lang' => current_language(), 'dir' => right_to_left() ? 'rtl' : 'ltr']);
    echo '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo html_writer::tag('title', $title);
    echo html_writer::empty_tag('link', ['rel' => 'stylesheet',
        'href' => (new moodle_url('/auth/coursereturn/confirm.css'))->out(false)]);
    echo '</head><body><main>';
    echo html_writer::tag('p', format_string(get_site()->fullname), ['class' => 'site']);
    echo html_writer::tag('h1', $title);
    echo html_writer::tag('p', $intro);
    echo html_writer::tag('p', get_string(
        'confirmpage_account',
        'auth_coursereturn',
        s(\auth_coursereturn\flow::masked_email($account->email))
    ), ['class' => 'account']);
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'data', 'value' => $data]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::tag('button', $button, ['type' => 'submit']);
    echo html_writer::end_tag('form');
    echo html_writer::tag('p', get_string('confirmpage_ignore', 'auth_coursereturn'), ['class' => 'note']);
    echo '</main></body></html>';
    exit;
}

require_sesskey();
$result = $auth->user_confirm($username, $secret);
if ($result === AUTH_CONFIRM_ALREADY) {
    if ($sameuser) {
        // A double click by the same authenticated person needs no extra login page.
        $SESSION->wantsurl = $finishurl->out(false);
        redirect($SESSION->wantsurl);
    }
    redirect(
        $loginurl,
        get_string('alreadyconfirmed_login', 'auth_coursereturn'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}
if ($result !== AUTH_CONFIRM_OK) {
    if ($auth->confirmation_state($username, $secret) === 'expired') {
        throw new moodle_exception('confirmationexpired', 'auth_coursereturn', $loginurl);
    }
    throw new moodle_exception('invalidconfirmdata');
}
$user = get_complete_user_data('username', $username);
complete_user_login($user);
\auth_coursereturn\confirmation_mfa::credit_email($result, (int)$user->id);
\core\session\manager::apply_concurrent_login_limit($user->id, session_id());
// Retain this across the MFA after_config hook on the following request.
$SESSION->wantsurl = $finishurl->out(false);
redirect($SESSION->wantsurl);
