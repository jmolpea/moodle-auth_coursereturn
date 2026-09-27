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
 * GET only renders a page that submits a sesskey-protected POST, so link previews
 * and mail scanners that do not run JavaScript cannot consume the confirmation.
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
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    // A plain GET is safe for mail scanners. The browser submits once after JS loads.
    // Do not load theme JS/AJAX here: concurrent anonymous requests can overwrite
    // the session cookie while complete_user_login rotates the session ID.
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; form-action 'self'; " .
        "base-uri 'none'; frame-ancestors 'none'");
    echo '<!doctype html>';
    echo html_writer::start_tag('html', ['lang' => current_language(), 'dir' => right_to_left() ? 'rtl' : 'ltr']);
    echo '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo html_writer::tag('title', get_string('confirming', 'auth_coursereturn'));
    echo '</head><body><main>';
    echo html_writer::tag('h1', get_string('confirming', 'auth_coursereturn'));
    echo html_writer::tag('p', get_string('confirming_desc', 'auth_coursereturn'));
    echo html_writer::start_tag('form', ['method' => 'post', 'id' => 'coursereturn-confirm',
        'action' => $PAGE->url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'data', 'value' => $data]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::tag(
        'button',
        get_string('confirmaccount', 'auth_coursereturn'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    );
    echo html_writer::end_tag('form');
    echo html_writer::tag('script', '', ['src' => (new moodle_url('/auth/coursereturn/confirm.js'))->out(false),
        'defer' => 'defer']);
    echo '</main></body></html>';
    exit;
}
require_sesskey();
$auth = get_auth_plugin('coursereturn');
$result = $auth->user_confirm($username, $secret);
if ($result === AUTH_CONFIRM_ALREADY) {
    if (isloggedin() && !isguestuser() && $USER->username === $username) {
        // A double click by the same authenticated person needs no extra login page.
        $SESSION->wantsurl = (new moodle_url('/auth/coursereturn/finish.php'))->out(false);
        redirect($SESSION->wantsurl);
    }
    // A confirmation link is not a reusable login link.
    redirect(
        new moodle_url('/login/index.php'),
        get_string('alreadyconfirmed'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}
if ($result !== AUTH_CONFIRM_OK) {
    throw new moodle_exception('invalidconfirmdata');
}
$user = get_complete_user_data('username', $username);
complete_user_login($user);
\auth_coursereturn\confirmation_mfa::credit_email($result, (int)$user->id);
\core\session\manager::apply_concurrent_login_limit($user->id, session_id());
// Retain this across the MFA after_config hook on the following request.
$SESSION->wantsurl = (new moodle_url('/auth/coursereturn/finish.php'))->out(false);
redirect($SESSION->wantsurl);
