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
 * View a single helpdesk ticket.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$ticketid = required_param('id', PARAM_INT);
$context  = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/view.php', ['id' => $ticketid]);
$PAGE->set_pagelayout('standard');

$ticket = $DB->get_record('local_helpdesk_tickets', ['id' => $ticketid], '*', MUST_EXIST);

$issupport = has_capability('local/helpdesk:viewalltickets', $context);
$isowner   = ($ticket->userid == $USER->id);

if (!$issupport && !$isowner) {
    throw new moodle_exception('accessdenied', 'local_helpdesk');
}

$PAGE->set_title(get_string('ticketdetails', 'local_helpdesk') . ' #' . $ticket->id);
$PAGE->set_heading(get_string('ticketdetails', 'local_helpdesk') . ' #' . $ticket->id);

// Fetch course info.
$coursename = get_string('nocourseguest', 'local_helpdesk');
if (!empty($ticket->courseid)) {
    $course = $DB->get_record('course', ['id' => $ticket->courseid], 'fullname');
    if ($course) {
        $coursename = format_string($course->fullname);
    }
}

// Fetch ticket owner.
$owner = $DB->get_record('user', ['id' => $ticket->userid]);

// Fetch assigned support.
$assignedname = get_string('unassigned', 'local_helpdesk');
if (!empty($ticket->assignedto)) {
    $support = $DB->get_record('user', ['id' => $ticket->assignedto]);
    if ($support) {
        $assignedname = fullname($support);
    }
}

// Fetch open chat session.
$chat = $DB->get_record('local_helpdesk_chats', ['ticketid' => $ticketid, 'status' => 'open']);

// Fetch feedback.
$feedback = $DB->get_record('local_helpdesk_feedback', ['ticketid' => $ticketid, 'userid' => $USER->id]);

// Fetch attachments.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_helpdesk', 'attachments', $ticketid, 'filename', false);
$attachmentlist = [];
foreach ($files as $file) {
    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );
    $attachmentlist[] = [
        'filename' => $file->get_filename(),
        'url'      => $url->out(false),
    ];
}

// Status options for support.
$statusoptions = [];
if ($issupport) {
    $allstatuses = ['open', 'inprogress', 'resolved', 'closed'];
    foreach ($allstatuses as $s) {
        $statusoptions[] = [
            'value'    => $s,
            'label'    => get_string('status_' . $s, 'local_helpdesk'),
            'selected' => ($ticket->status === $s),
        ];
    }
}

$templatedata = [
    'ticket' => [
        'id'           => $ticket->id,
        'subject'      => format_string($ticket->subject),
        'description'  => format_text($ticket->description, $ticket->descriptionformat),
        'priority'     => get_string('priority' . $ticket->priority, 'local_helpdesk'),
        'prioritykey'  => $ticket->priority,
        'status'       => get_string('status_' . $ticket->status, 'local_helpdesk'),
        'statuskey'    => $ticket->status,
        'timecreated'  => userdate($ticket->timecreated),
        'coursename'   => $coursename,
        'ownerfullname'=> $owner ? fullname($owner) : get_string('deleteduser', 'core'),
        'assignedname' => $assignedname,
        'attachments'  => $attachmentlist,
        'hasattachments' => !empty($attachmentlist),
    ],
    'issupport'         => $issupport,
    'isowner'           => $isowner,
    'statusoptions'     => $statusoptions,
    'haschat'           => !empty($chat),
    'chatid'            => !empty($chat) ? $chat->id : 0,
    'canopenchat'       => ($issupport && empty($chat)),
    'canopenfeedback'   => ($isowner && empty($feedback)
                            && in_array($ticket->status, ['resolved', 'closed'])),
    'hasfeedback'       => !empty($feedback),
    'feedbackrating'    => !empty($feedback) ? $feedback->rating : 0,
    'feedbackcomment'   => !empty($feedback) ? $feedback->comment : '',
    'ticketlisturl'     => (new moodle_url('/local/helpdesk/index.php'))->out(false),
    'manageurl'         => (new moodle_url('/local/helpdesk/manage.php'))->out(false),
];

// Load AMD for chat interface + unread poller.
$PAGE->requires->js_call_amd('local_helpdesk/helpdesk', 'initChatWidget', [
    [
        'ticketid'   => $ticketid,
        'chatid'     => !empty($chat) ? $chat->id : 0,
        'issupport'  => $issupport,
        'subject'    => format_string($ticket->subject),
    ],
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_helpdesk/ticket_view', $templatedata);
echo $OUTPUT->footer();
