<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Technical support management view — all tickets in the system.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/manage.php');
$PAGE->set_title(get_string('managetickets', 'local_helpdesk'));
$PAGE->set_heading(get_string('managetickets', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

require_capability('local/helpdesk:viewalltickets', $context);

$statusfilter = optional_param('status', '', PARAM_TEXT);

// Build query with optional status filter.
$params = [];
$where  = '1=1';
if (in_array($statusfilter, ['open', 'inprogress', 'resolved', 'closed'])) {
    $where    = 'status = :status';
    $params['status'] = $statusfilter;
}

$tickets = $DB->get_records_select(
    'local_helpdesk_tickets',
    $where,
    $params,
    'timecreated DESC'
);

$ticketrows = [];
foreach ($tickets as $ticket) {
    $owner = $DB->get_record('user', ['id' => $ticket->userid], 'id,firstname,lastname,username');

    $coursename = get_string('nocourseguest', 'local_helpdesk');
    if (!empty($ticket->courseid)) {
        $course = $DB->get_record('course', ['id' => $ticket->courseid], 'fullname');
        if ($course) {
            $coursename = format_string($course->fullname);
        }
    }

    $ticketrows[] = [
        'id'          => $ticket->id,
        'subject'     => format_string($ticket->subject),
        'ownername'   => $owner ? fullname($owner) : '?',
        'ownerusername' => $owner ? $owner->username : '',
        'coursename'  => $coursename,
        'priority'    => get_string('priority' . $ticket->priority, 'local_helpdesk'),
        'prioritykey' => $ticket->priority,
        'status'      => get_string('status_' . $ticket->status, 'local_helpdesk'),
        'statuskey'   => $ticket->status,
        'timecreated' => userdate($ticket->timecreated),
        'viewurl'     => (new moodle_url('/local/helpdesk/view.php', ['id' => $ticket->id]))->out(false),
    ];
}

$statusfilters = [];
foreach (['', 'open', 'inprogress', 'resolved', 'closed'] as $s) {
    $label = $s === '' ? get_string('alltickets', 'local_helpdesk') : get_string('status_' . $s, 'local_helpdesk');
    $statusfilters[] = [
        'value'    => $s,
        'label'    => $label,
        'selected' => ($statusfilter === $s),
        'url'      => (new moodle_url('/local/helpdesk/manage.php', ['status' => $s]))->out(false),
    ];
}

$templatedata = [
    'tickets'       => $ticketrows,
    'notickets'     => empty($ticketrows),
    'statusfilters' => $statusfilters,
    'adminlogurl'   => (new moodle_url('/local/helpdesk/admin_log.php'))->out(false),
    'isadmin'       => has_capability('local/helpdesk:viewlog', $context),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_helpdesk/manage_tickets', $templatedata);
echo $OUTPUT->footer();
