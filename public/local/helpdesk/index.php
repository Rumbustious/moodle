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
 * Helpdesk main page — shows the current user's tickets.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/index.php');
$PAGE->set_title(get_string('helpdesk', 'local_helpdesk'));
$PAGE->set_heading(get_string('helpdesk', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

// Redirect technical support to their view.
if (has_capability('local/helpdesk:viewalltickets', $context)) {
    redirect(new moodle_url('/local/helpdesk/manage.php'));
}

require_capability('local/helpdesk:viewowntickets', $context);

// Fetch the user's tickets.
$tickets = $DB->get_records_select(
    'local_helpdesk_tickets',
    'userid = :userid',
    ['userid' => $USER->id],
    'timecreated DESC'
);

// Build ticket rows for the template.
$ticketrows = [];
foreach ($tickets as $ticket) {
    $coursename = get_string('nocourseguest', 'local_helpdesk');
    if (!empty($ticket->courseid)) {
        $course = $DB->get_record('course', ['id' => $ticket->courseid], 'fullname');
        if ($course) {
            $coursename = format_string($course->fullname);
        }
    }
    $ticketrows[] = [
        'id'           => $ticket->id,
        'subject'      => format_string($ticket->subject),
        'coursename'   => $coursename,
        'priority'     => get_string('priority' . $ticket->priority, 'local_helpdesk'),
        'prioritykey'  => $ticket->priority,
        'status'       => get_string('status_' . $ticket->status, 'local_helpdesk'),
        'statuskey'    => $ticket->status,
        'timecreated'  => userdate($ticket->timecreated),
        'viewurl'      => (new moodle_url('/local/helpdesk/view.php', ['id' => $ticket->id]))->out(false),
    ];
}

$opencount = $DB->count_records_select(
    'local_helpdesk_tickets',
    "userid = :userid AND status IN ('open','inprogress')",
    ['userid' => $USER->id]
);

$templatedata = [
    'tickets'        => $ticketrows,
    'notickets'      => empty($ticketrows),
    'createurl'      => (new moodle_url('/local/helpdesk/create.php'))->out(false),
    'cancreate'      => ($opencount < 3),
    'maxopentickets' => get_string('maxopentickets', 'local_helpdesk', 3),
];

// Load AMD module for chat popup / unread count polling.
$PAGE->requires->js_call_amd('local_helpdesk/helpdesk', 'initUnreadPoller');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_helpdesk/ticket_list', $templatedata);
echo $OUTPUT->footer();
