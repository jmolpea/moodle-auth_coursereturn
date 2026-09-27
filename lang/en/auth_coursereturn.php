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
 * Language strings.
 *
 * @package    auth_coursereturn
 * @category   string
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_coursereturndescription'] = 'Email self-registration that returns new users to their original course after confirmation.';
$string['autoenrol'] = 'Enrol automatically when permitted';
$string['autoenrol_desc'] = 'After email confirmation and required access checks, enrol the user only when a self-enrolment instance without a password or group key permits it. Existing enrolments are never reactivated. Otherwise show the normal course access page.';
$string['check'] = 'Inspect without changes';
$string['cleanup'] = 'Remove expired course destinations';
$string['confirmaccount'] = 'Confirm my account and continue';
$string['confirmationttl'] = 'Confirmation link lifetime';
$string['confirmationttl_desc'] = 'Maximum age from account creation. Moodle may delete unconfirmed accounts earlier according to site settings. Default: 7 days. Resending does not extend the lifetime.';
$string['confirming'] = 'Confirming your account';
$string['confirming_desc'] = 'You will continue automatically. If your browser does not continue, use the button below.';
$string['description'] = 'Enable this authentication method and select it as the self-registration method in Manage authentication. Link visitors to /auth/coursereturn/start.php?id=COURSEID or /course/view.php?id=COURSEID. Existing email accounts remain unchanged. MFA, policies and required profile fields must be completed before course access. Do not exclude finish.php from MFA. Enable reCAPTCHA to limit automated sign-ups.';
$string['diag_activeenrol'] = 'Active enrolment';
$string['diag_auth'] = 'Authentication method';
$string['diag_autoenrol'] = 'Automatic enrolment';
$string['diag_confirmed'] = 'Confirmed';
$string['diag_coursefromurl'] = 'Course from original URL';
$string['diag_coursevisible'] = 'Course visible to user';
$string['diag_currentlogin'] = 'Current login';
$string['diag_divert'] = 'Email diversion configured (divertallemailsto)';
$string['diag_emailfailed'] = 'Failed email events (all email types)';
$string['diag_enabled'] = 'Authentication method enabled';
$string['diag_enrolself'] = 'Capability enrol/self:enrolself';
$string['diag_lastemailfailed'] = 'Last failed email event';
$string['diag_lastlogin'] = 'Last login';
$string['diag_mfa'] = 'MFA enabled';
$string['diag_moodle'] = 'Moodle release';
$string['diag_noemailever'] = 'All email disabled (noemailever)';
$string['diag_pendingcourse'] = 'Pending course';
$string['diag_pendingexpiry'] = 'Pending course expiry';
$string['diag_policyagreed'] = 'Site policy agreed';
$string['diag_policyhandler'] = 'Site policy handler';
$string['diag_policyversion'] = 'Policy version #{$a}';
$string['diag_registerauth'] = 'Self-registration method (registerauth)';
$string['diag_selfenabled'] = 'Self enrolment plugin enabled';
$string['diag_selfinstance'] = 'Self enrolment instance #{$a}';
$string['diag_smtp'] = 'SMTP configured';
$string['diag_suspendeddeleted'] = 'Suspended / deleted';
$string['diag_timecreated'] = 'Account created';
$string['diag_ttl'] = 'Confirmation link lifetime (seconds)';
$string['diag_version'] = 'Plugin version';
$string['diag_wantsurl'] = 'Original destination saved';
$string['diag_wwwroot'] = 'Site URL (wwwroot)';
$string['diagnostics'] = 'Registration and course return diagnostics';
$string['diagnostics_desc'] = 'Administrators only. This page does not change accounts, confirm links or expose passwords or tokens. Inspect a test account to distinguish configuration, saved course and login state.';
$string['diagnostics_interpretation'] = 'An unconfirmed account without an original course lost its destination before the email was sent. A confirmed account has already used its link or was confirmed by an administrator: reusing the link will not log in again. Temporary destinations are removed after completing the return, so their later absence does not prove a failure. A valid pending course can be resumed through normal login. Enable automatic enrolment to enrol automatically. Zero failed email events does not prove delivery: check the mail server logs, the spam folder and greylisting.';
$string['diagnosticsvalue'] = 'Value';
$string['entrylink'] = 'Course registration entry link';
$string['locktimeout'] = 'Another request is being processed. Please try again shortly.';
$string['otheraccount'] = 'Another account is signed in. Open this confirmation link in a private window or sign out first.';
$string['pluginname'] = 'Email registration with course return';
$string['privacy:pending'] = 'Temporary course identifier and expiry time used to complete a confirmed registration. Removed on completion or after 24 hours by the scheduled task.';
$string['signupnotice'] = 'After confirming your email and completing any required access checks, you will return to {$a}.';
$string['signupnotice_enrol'] = 'By creating this account to access {$a}, you request enrolment in this course. After email confirmation and required access checks, you will be enrolled automatically if the course allows self-enrolment without a key.';
$string['usernotfound'] = 'Local account not found.';
