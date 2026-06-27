<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Export all helpdesk tickets.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$tickets = $DB->get_records('local_helpdesk_tickets', null, 'timecreated ASC');
$ticketids = array_keys($tickets);

$messagesbyticket = [];
$maxresponses = 0;

if ($ticketids) {
    list($insql, $inparams) = $DB->get_in_or_equal($ticketids, SQL_PARAMS_NAMED);
    $messages = $DB->get_records_sql(
        "SELECT m.id, c.ticketid, m.userid, m.message, m.timecreated,
                u.firstname, u.lastname, u.username
           FROM {local_helpdesk_messages} m
           JOIN {local_helpdesk_chats} c ON c.id = m.chatid
      LEFT JOIN {user} u ON u.id = m.userid
          WHERE c.ticketid $insql
       ORDER BY c.ticketid ASC, m.timecreated ASC, m.id ASC",
        $inparams
    );

    foreach ($messages as $message) {
        if (!isset($messagesbyticket[$message->ticketid])) {
            $messagesbyticket[$message->ticketid] = [];
        }

        $sender = empty($message->userid) ? get_string('system', 'core') : fullname($message);
        $messagesbyticket[$message->ticketid][] = userdate($message->timecreated) . ' - ' . $sender . ': ' . $message->message;
        $maxresponses = max($maxresponses, count($messagesbyticket[$message->ticketid]));
    }
}

$filename = 'helpdesk-tickets-' . date('Ymd-His');
$export = new csv_export_writer();
$export->set_filename($filename);

$headers = [
    'Ticket_ID',
    'Subject',
    'Description',
    'Priority',
    'Status',
    'Created_By',
    'Created_By_Username',
    'Course',
    'Assigned_To',
    'Assigned_To_Username',
    'Time_Created',
    'Time_Modified',
    'Chat_Status',
    'Feedback_Rating',
    'Feedback_Comment',
    'Feedback_Time',
];

for ($i = 1; $i <= $maxresponses; $i++) {
    $headers[] = 'Response_' . $i;
}

$export->add_data($headers);

foreach ($tickets as $ticket) {
    $owner = $DB->get_record('user', ['id' => $ticket->userid], 'id,firstname,lastname,username');
    $assignee = !empty($ticket->assignedto)
        ? $DB->get_record('user', ['id' => $ticket->assignedto], 'id,firstname,lastname,username')
        : null;
    $course = !empty($ticket->courseid)
        ? $DB->get_record('course', ['id' => $ticket->courseid], 'id,fullname')
        : null;
    $chat = $DB->get_record_sql(
        "SELECT *
           FROM {local_helpdesk_chats}
          WHERE ticketid = :ticketid
       ORDER BY timecreated DESC, id DESC",
        ['ticketid' => $ticket->id],
        IGNORE_MULTIPLE
    );
    $feedback = $DB->get_record('local_helpdesk_feedback', ['ticketid' => $ticket->id]);

    $row = [
        $ticket->id,
        $ticket->subject,
        trim(html_to_text($ticket->description, 0, false)),
        $ticket->priority,
        $ticket->status,
        $owner ? fullname($owner) : get_string('deleteduser', 'core'),
        $owner->username ?? '',
        $course ? format_string($course->fullname) : get_string('nocourseguest', 'local_helpdesk'),
        $assignee ? fullname($assignee) : get_string('unassigned', 'local_helpdesk'),
        $assignee->username ?? '',
        userdate($ticket->timecreated),
        userdate($ticket->timemodified),
        $chat->status ?? '',
        $feedback->rating ?? '',
        $feedback->comment ?? '',
        !empty($feedback->timecreated) ? userdate($feedback->timecreated) : '',
    ];

    $responses = $messagesbyticket[$ticket->id] ?? [];
    for ($i = 0; $i < $maxresponses; $i++) {
        $row[] = $responses[$i] ?? '';
    }

    $export->add_data($row);
}

$export->download_file();
exit;
