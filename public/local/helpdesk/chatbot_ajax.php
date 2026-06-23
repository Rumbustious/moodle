<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_helpdesk
// @copyright  2026 Helpdesk Plugin
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_helpdesk\faq_service;
use local_helpdesk\ai_service;

require_login();

$context = context_system::instance();
// require_capability('local_helpdesk:viewowntickets', $context);

header('Content-Type: application/json');

$question = optional_param('question', '', PARAM_TEXT);

if (!$question) {
    echo json_encode(['error' => get_string('chatbotemptyquestion', 'local_helpdesk')]);
    exit;
}

// Step 1: Try FAQ service
$faqanswer = faq_service::find_answer($question);
if ($faqanswer) {
    echo json_encode(['reply' => $faqanswer, 'escalated' => false]);
    exit;
}

// Step 2: Try AI service
$ai = ai_service::ask_llm($question);
if (!$ai) {
    echo json_encode(['error' => 'AI service unavailable. Please try again later.']);
    exit;
}

if (!empty($ai['escalate'])) {
    // Check open tickets limit
    $opencount = $DB->count_records_select(
        'local_helpdesk_tickets',
        "userid = :userid AND status IN ('open','inprogress')",
        ['userid' => $USER->id]
    );

    if ($opencount >= 3) {
        echo json_encode(['reply' => get_string('chatbotmaxopen', 'local_helpdesk'), 'escalated' => false]);
        exit;
    }

    // Prepare ticket proposal (no DB insert yet)
    $proposed = [
        'subject'     => clean_param($ai['ticket_summary'], PARAM_TEXT),
        'priority'    => $ai['priority'] ?? 'medium',
        'description' => $question . "\n\nAI Analysis:\n" . $ai['ticket_summary'],
        'ai_response' => $ai['ticket_summary']   // <-- ADD THIS LINE

    ];

    echo json_encode([
        'needs_confirmation' => true,
        'proposed_ticket'    => $proposed,
        'reply'              => get_string('chatbotescalationproposal', 'local_helpdesk')
    ]);
    exit;
}

// Standard AI response
echo json_encode(['reply' => $ai['answer'], 'escalated' => false]);