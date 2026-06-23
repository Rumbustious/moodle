<?php
/**
 * Customer-service chatbot page.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_helpdesk\faq_service;
use local_helpdesk\ai_service;

// 1. Moodle Page Setup
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/chatbot.php');
$PAGE->set_title(get_string('chatbottitle', 'local_helpdesk'));
$PAGE->set_heading(get_string('chatbottitle', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

// Capability check
// require_capability('local_helpdesk:viewowntickets', $context);

// 2. Variables & Parameters
$question = optional_param('question', '', PARAM_TEXT);
$response = '';
$createdticketid = 0;

// 3. Logic Processing
if (data_submitted() && confirm_sesskey()) {
    $question = trim($question);

    if ($question === '') {
        $response = get_string('chatbotemptyquestion', 'local_helpdesk');
    } else {
        // STEP 1: Try FAQ Service first
        $faqanswer = faq_service::find_answer($question);

        if ($faqanswer) {
            $response = $faqanswer;
        } else {
            // STEP 2: Try AI Service
            $ai = ai_service::ask_llm($question);

            if (!$ai) {
                $response = "AI service unavailable. Please try again later.";
            } else if (!empty($ai['escalate'])) {
                // STEP 3: Escalation (Create Ticket)
                // require_capability('local_helpdesk:submitticket', $context);

                // Rate limiting check
                $opencount = $DB->count_records_select(
                    'local_helpdesk_tickets',
                    "userid = :userid AND status IN ('open','inprogress')",
                    ['userid' => $USER->id]
                );

                if ($opencount >= 3) {
                    $response = get_string('chatbotmaxopen', 'local_helpdesk');
                } else {
                    $now = time();
                    $ticket = new stdClass();
                    $ticket->userid = $USER->id;
                    $ticket->subject = clean_param($ai['ticket_summary'], PARAM_TEXT);
                    $ticket->description = $question . "\n\nAI Analysis:\n" . $ai['ticket_summary'];
                    $ticket->descriptionformat = FORMAT_HTML;
                    $ticket->priority = $ai['priority'] ?? 'medium';
                    $ticket->status = 'open';
                    $ticket->timecreated = $now;
                    $ticket->timemodified = $now;

                    $createdticketid = $DB->insert_record('local_helpdesk_tickets', $ticket);

                    // Log the escalation
                    $DB->insert_record('local_helpdesk_ticket_log', (object)[
                        'ticketid' => $createdticketid,
                        'userid' => $USER->id,
                        'action' => 'created_by_chatbot',
                        'detail' => 'Ticket auto-created from chatbot escalation.',
                        'timecreated' => $now,
                    ]);

                    $response = get_string('chatbotfallbackcreated', 'local_helpdesk', $createdticketid);
                }
            } else {
                // Standard AI Response
                $response = $ai['answer'];
            }
        }
    }
}

// 4. Output Generation
echo $OUTPUT->header();
?>
<div class="container">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title"><?php echo get_string('chatbottitle', 'local_helpdesk'); ?></h3>
            <p class="text-muted"><?php echo get_string('chatbotintro', 'local_helpdesk'); ?></p>

            <form method="post" action="<?php echo $PAGE->url->out(false); ?>">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <div class="form-group">
                    <label for="chatbot-question"><?php echo get_string('chatbotquestionlabel', 'local_helpdesk'); ?></label>
                    <textarea 
                        id="chatbot-question" 
                        name="question" 
                        class="form-control" 
                        rows="4" 
                        required
                    ><?php echo s($question); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-2"><?php echo get_string('chatbotaskbutton', 'local_helpdesk'); ?></button>
                <a href="<?php echo new moodle_url('/local/helpdesk/index.php'); ?>" class="btn btn-secondary mt-2">
                    <?php echo get_string('mytickets', 'local_helpdesk'); ?>
                </a>
            </form>

            <?php if ($response !== ''): ?>
                <hr>
                <h4><?php echo get_string('chatbotanswerlabel', 'local_helpdesk'); ?></h4>
                <div class="alert <?php echo $createdticketid ? 'alert-warning' : 'alert-info'; ?> mb-0">
                    <?php echo format_text($response, FORMAT_HTML); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();