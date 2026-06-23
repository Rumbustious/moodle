<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_helpdesk
// @copyright  2026 Helpdesk Plugin
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/helpdesk:viewowntickets', $context);

header('Content-Type: application/json');

$subject     = required_param('subject', PARAM_TEXT);
$priority    = required_param('priority', PARAM_ALPHA);
$description = required_param('description', PARAM_TEXT);
$question    = required_param('question', PARAM_TEXT);          // original user message
$ai_response = required_param('ai_response', PARAM_TEXT);       // AI's analysis (ticket summary or full answer)

$validpriorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $validpriorities)) {
    $priority = 'medium';
}

// Double‑check open tickets limit
$opencount = $DB->count_records_select(
    'local_helpdesk_tickets',
    "userid = :userid AND status IN ('open','inprogress')",
    ['userid' => $USER->id]
);
if ($opencount >= 3) {
    echo json_encode(['error' => get_string('chatbotmaxopen', 'local_helpdesk')]);
    exit;
}

$now = time();

// 1. Create ticket
$ticket = new stdClass();
$ticket->userid          = $USER->id;
$ticket->subject         = $subject;
$ticket->description     = $description;
$ticket->descriptionformat = FORMAT_HTML;
$ticket->priority        = $priority;
$ticket->status          = 'open';
$ticket->timecreated     = $now;
$ticket->timemodified    = $now;
$ticketid = $DB->insert_record('local_helpdesk_tickets', $ticket);

// Log creation
$DB->insert_record('local_helpdesk_ticket_log', (object)[
    'ticketid'   => $ticketid,
    'userid'     => $USER->id,
    'action'     => 'created_by_chatbot',
    'detail'     => 'Ticket created after user confirmation.',
    'timecreated'=> $now,
]);

// 2. Create chat session for this ticket
$chat = new stdClass();
$chat->ticketid   = $ticketid;
$chat->status     = 'open';          // open chat
$chat->timecreated = $now;
$chatid = $DB->insert_record('local_helpdesk_chats', $chat);

// 3. Insert initial messages into the chat
//    - user's original question (as the ticket owner)
$user_message = new stdClass();
$user_message->chatid     = $chatid;
$user_message->userid     = $USER->id;
$user_message->message    = $question;
$user_message->timecreated = $now;
$DB->insert_record('local_helpdesk_messages', $user_message);

//    - AI's analysis (as a system message, userid = 0 or a special bot)
//      We'll use userid = 0 to indicate system/AI message.
$ai_message = new stdClass();
$ai_message->chatid     = $chatid;
$ai_message->userid     = 0;          // system / AI
$ai_message->message    = "AI analysis:\n" . $ai_response;
$ai_message->timecreated = $now;
$DB->insert_record('local_helpdesk_messages', $ai_message);

echo json_encode([
    'success'   => true,
    'ticketid'  => $ticketid,
    'chatid'    => $chatid,
    'reply'     => get_string('chatbotfallbackcreated', 'local_helpdesk', $ticketid)
]);