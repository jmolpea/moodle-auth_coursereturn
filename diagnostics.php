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
 * Read-only diagnostics for administrators. Never shows emails, passwords or secrets.
 *
 * @package    auth_coursereturn
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/auth/coursereturn/diagnostics.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('diagnostics', 'auth_coursereturn'));
$PAGE->set_heading(format_string($SITE->fullname));
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$username = '';
$user = false;
if (data_submitted()) {
    require_sesskey();
    $username = optional_param('username', '', PARAM_RAW_TRIMMED);
    if ($username !== '') {
        $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
    }
}
$yesno = static fn($value) => get_string($value ? 'yes' : 'no');
$label = static fn(string $key, $a = null) => get_string('diag_' . $key, 'auth_coursereturn', $a);
$rows = [
    [$label('moodle'), s($CFG->release)],
    [$label('wwwroot'), s($CFG->wwwroot)],
    [$label('version'), s((string)get_config('auth_coursereturn', 'version'))],
    [$label('enabled'), $yesno(is_enabled_auth('coursereturn'))],
    [$label('registerauth'), s((string)get_config('core', 'registerauth'))],
    [$label('autoenrol'), $yesno(get_config('auth_coursereturn', 'autoenrol'))],
    [$label('mfa'), $yesno(get_config('tool_mfa', 'enabled'))],
    [$label('ttl'), (int)get_config('auth_coursereturn', 'confirmationttl')],
    [$label('smtp'), $yesno(!empty($CFG->smtphosts))],
    [$label('noemailever'), $yesno(!empty($CFG->noemailever))],
    [$label('divert'), $yesno(!empty($CFG->divertallemailsto))],
    [$label('policyhandler'), s($CFG->sitepolicyhandler ?? '')],
];
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('diagnostics', 'auth_coursereturn'));
echo html_writer::tag('p', get_string('diagnostics_desc', 'auth_coursereturn'));
$table = new html_table();
$table->head = [get_string('name'), get_string('diagnosticsvalue', 'auth_coursereturn')];
$table->data = $rows;
echo html_writer::table($table);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('username'), ['for' => 'diagnosticusername']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'username', 'id' => 'diagnosticusername',
    'value' => $username, 'class' => 'form-control', 'autocomplete' => 'off']);
echo html_writer::tag('button', get_string('check', 'auth_coursereturn'), ['type' => 'submit', 'class' => 'btn btn-primary mt-2']);
echo html_writer::end_tag('form');
if ($username !== '' && !$user) {
    echo $OUTPUT->notification(get_string('usernotfound', 'auth_coursereturn'));
}
if ($user) {
    // Read raw preferences directly; this diagnostic never consumes or clears a destination.
    $source = get_user_preferences('auth_email_wantsurl', '', $user->id);
    $pending = json_decode(get_user_preferences(\auth_coursereturn\flow::PREFERENCE, '', $user->id), true);
    $courseid = \auth_coursereturn\flow::course_from_url($source);
    $pendingid = is_array($pending) ? (int)($pending['courseid'] ?? 0) : 0;
    $expires = is_array($pending) ? (int)($pending['expires'] ?? 0) : 0;
    $table->data = [
        [$label('auth'), s($user->auth)],
        [$label('confirmed'), $yesno($user->confirmed)],
        [$label('suspendeddeleted'), $yesno($user->suspended) . ' / ' . $yesno($user->deleted)],
        [$label('timecreated'), userdate($user->timecreated)],
        [$label('lastlogin'), $user->lastlogin ? userdate($user->lastlogin) : '-'],
        [$label('currentlogin'), $user->currentlogin ? userdate($user->currentlogin) : '-'],
        [$label('wantsurl'), $yesno($source !== '')],
        [$label('coursefromurl'), $courseid ?: '-'],
        [$label('pendingcourse'), $pendingid ?: '-'],
        [$label('pendingexpiry'), $expires ? userdate($expires) : '-'],
        [$label('policyagreed'), $yesno($user->policyagreed)],
    ];
    if ($DB->get_manager()->table_exists('logstore_standard_log')) {
        $params = ['event' => '\\core\\event\\email_failed', 'userid' => $user->id];
        // Never display event.other: Moodle stores the email body, including its token, there.
        $failures = $DB->get_record_sql('SELECT COUNT(1) AS total, MAX(timecreated) AS lasttime
                                           FROM {logstore_standard_log}
                                          WHERE eventname = :event AND relateduserid = :userid', $params);
        $table->data[] = [$label('emailfailed'), (int)$failures->total];
        $table->data[] = [$label('lastemailfailed'), $failures->lasttime ? userdate($failures->lasttime) : '-'];
    }
    if (($CFG->sitepolicyhandler ?? '') === 'tool_policy') {
        foreach (\tool_policy\api::list_current_versions(\tool_policy\policy_version::AUDIENCE_LOGGEDIN) as $policy) {
            $status = $DB->get_field(
                'tool_policy_acceptances',
                'status',
                ['userid' => $user->id, 'policyversionid' => $policy->id]
            );
            $table->data[] = [$label('policyversion', $policy->id),
                $status === false ? '-' : $yesno((int)$status === 1)];
        }
    }
    echo html_writer::table($table);
    echo html_writer::tag('p', get_string('diagnostics_interpretation', 'auth_coursereturn'));
    $id = $pendingid ?: $courseid;
    if ($id && ($course = $DB->get_record('course', ['id' => $id]))) {
        $context = context_course::instance($id);
        echo $OUTPUT->heading(format_string($course->fullname, true, ['context' => $context]), 3);
        echo html_writer::link(
            new moodle_url('/auth/coursereturn/start.php', ['id' => $id]),
            get_string('entrylink', 'auth_coursereturn')
        );
        $table->data = [
            [$label('coursevisible'), $yesno(\core_course_category::can_view_course_info($course, $user))],
            [$label('enrolself'), $yesno(has_capability('enrol/self:enrolself', $context, $user))],
            [$label('activeenrol'), $yesno(is_enrolled($context, $user, '', true))],
            [$label('selfenabled'), $yesno(enrol_is_enabled('self'))],
        ];
        foreach (enrol_get_instances($id, false) as $instance) {
            if ($instance->enrol === 'self') {
                $table->data[] = [$label('selfinstance', $instance->id), s(json_encode([
                    'enabled' => $instance->status == ENROL_INSTANCE_ENABLED,
                    'newenrolments' => (bool)$instance->customint6,
                    'haskey' => !empty($instance->password),
                    'groupkey' => (bool)$instance->customint1,
                    'start' => (int)$instance->enrolstartdate,
                    'end' => (int)$instance->enrolenddate,
                    'cohortrestriction' => (bool)$instance->customint5,
                    'capacity' => (int)$instance->customint3,
                    'enrolled' => $DB->count_records('user_enrolments', ['enrolid' => $instance->id]),
                ]))];
            }
        }
        echo html_writer::table($table);
    }
}
echo $OUTPUT->footer();
