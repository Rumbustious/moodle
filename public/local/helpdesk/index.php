<?php
// This file is part of Moodle - http://moodle.org/
//
// Helpdesk main page — shows the current user's tickets + AI chat widget with confirmation.
//
// @package    local_helpdesk
// @copyright  2026 Helpdesk Plugin
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

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

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_helpdesk/ticket_list', $templatedata);
?>

<!-- ========== AI CHAT WIDGET (embedded directly) ========== -->
<style>
#ai-chat-wrapper {
    position: fixed; bottom: 20px; right: 20px; z-index: 9999;
    font-family: 'Segoe UI', Arial, sans-serif;
}
#ai-chat-icon {
    width: 60px; height: 60px; background: #007bff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,.2);
    font-size: 24px;
    color: white;
}
#ai-chat-window {
    display: none; width: 400px; height: 500px; background: #fff;
    border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.15);
    flex-direction: column; overflow: hidden; margin-bottom: 15px;
    border: 1px solid #ddd;
}
#ai-chat-header {
    background: #007bff; color: #fff; padding: 15px; font-weight: bold;
    display: flex; justify-content: space-between; align-items: center;
}
.header-controls {
    display: flex; gap: 12px;
}
.header-controls button {
    background: transparent; border: none; color: white;
    font-size: 18px; cursor: pointer; padding: 0;
    width: 28px; height: 28px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.header-controls button:hover {
    background: rgba(255,255,255,0.2);
}
.header-controls button.off {
    opacity: 0.6;
    background: rgba(0,0,0,0.2);
}
#ai-chat-body {
    flex: 1; padding: 15px; overflow-y: auto; background: #f9f9fb;
    display: flex; flex-direction: column; gap: 12px;
}
.chat-bubble {
    padding: 12px 16px; border-radius: 15px; max-width: 85%;
    font-size: 14px; line-height: 1.6; word-wrap: break-word;
}
.user-msg {
    background: #007bff; color: #fff; align-self: flex-end;
    border-bottom-right-radius: 2px;
}
.ai-msg {
    background: #fff; color: #333; align-self: flex-start;
    border-bottom-left-radius: 2px; border: 1px solid #e0e0e0;
}
#ai-chat-footer {
    padding: 10px; border-top: 1px solid #eee; display: flex;
    gap: 8px; background: #fff;
}
#ai-chat-input {
    flex: 1; border: 1px solid #ddd; border-radius: 20px;
    padding: 10px 15px; height: 42px; resize: none; outline: none;
}
#ai-chat-send, #ai-chat-mic {
    background: #007bff; color: #fff; border: none; border-radius: 50%;
    width: 40px; height: 40px; cursor: pointer;
}
#ai-chat-mic.listening {
    background: #dc3545;
}
#ai-chat-mic:disabled {
    background: #cccccc;
    cursor: not-allowed;
    opacity: 0.6;
}
.typing {
    color: #666;
    font-style: italic;
    padding: 8px 12px;
}
.confirmation-buttons {
    margin-top: 10px;
    display: flex;
    gap: 8px;
}
</style>

<div id="ai-chat-wrapper">
    <div id="ai-chat-window">
        <div id="ai-chat-header">
            <span>AI Support Assistant</span>
            <div class="header-controls">
                <button id="voice-toggle-btn" title="Toggle voice output">🔊</button>
                <button id="mic-toggle-btn" title="Toggle microphone input">🎤❌</button>
                <span id="close-chat" style="cursor:pointer;font-size:20px;">&times;</span>
            </div>
        </div>
        <div id="ai-chat-body"></div>
        <div id="ai-chat-footer">
            <textarea id="ai-chat-input" placeholder="Ask a question..."></textarea>
            <button id="ai-chat-mic" title="Voice to text">🎤</button>
            <button id="ai-chat-send" title="Send">➤</button>
        </div>
    </div>
    <div id="ai-chat-icon">💬</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(function() {
    if (window.chatWidgetInitialized) return;
    window.chatWidgetInitialized = true;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const hasSpeechRecognition = !!SpeechRecognition;
    const hasSpeechSynthesis = 'speechSynthesis' in window;
    let recognition = null;
    let sessionHistory = [];

    let voiceEnabled = true;
    let micEnabled = true;

    const $voiceToggleBtn = $('#voice-toggle-btn');
    const $micToggleBtn = $('#mic-toggle-btn');
    const $micButton = $('#ai-chat-mic');

    function updateVoiceToggleUI() {
        if (voiceEnabled) {
            $voiceToggleBtn.html('🔊').removeClass('off').attr('title', 'Disable voice output');
        } else {
            $voiceToggleBtn.html('🔇').addClass('off').attr('title', 'Enable voice output');
        }
    }

    function updateMicToggleUI() {
        if (micEnabled) {
            $micToggleBtn.html('🎤').removeClass('off').attr('title', 'Disable microphone input');
            $micButton.prop('disabled', false);
        } else {
            $micToggleBtn.html('🚫🎤').addClass('off').attr('title', 'Enable microphone input');
            $micButton.prop('disabled', true);
            if (recognition) {
                try { recognition.stop(); } catch(e) {}
                recognition = null;
                $micButton.removeClass('listening');
            }
        }
    }

    if (!hasSpeechRecognition) {
        $micButton.prop('disabled', true).attr('title', 'Voice input not supported');
        micEnabled = false;
        updateMicToggleUI();
    }

    const scrollToBottom = () => {
        $('#ai-chat-body').animate({scrollTop: $('#ai-chat-body')[0].scrollHeight}, 300);
    };

    const renderBubble = (content, type) => $('<div>').addClass('chat-bubble ' + type).html(content);

    const loadHistory = () => {
        const $body = $('#ai-chat-body');
        $body.empty();
        if (sessionHistory.length === 0) {
            $body.append(renderBubble('Hello! I\'m your support assistant. Ask me anything about your courses or issues.', 'ai-msg'));
        } else {
            sessionHistory.forEach(item => {
                $body.append(renderBubble(item.question, 'user-msg'));
                $body.append(renderBubble(item.answer, 'ai-msg'));
            });
        }
        scrollToBottom();
    };

    const speakResponse = (text) => {
        if (!voiceEnabled || !hasSpeechSynthesis) return;
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utterance);
    };

    // Show confirmation buttons inside the last AI bubble
    function showConfirmationButtons(containerId, proposedTicket, originalQuestion) {
        const $container = $('#' + containerId);
        const buttonsHtml = `
            <div class="confirmation-buttons">
                <button class="btn btn-sm btn-success confirm-yes">Create ticket</button>
                <button class="btn btn-sm btn-secondary confirm-no">No, thanks</button>
            </div>
        `;
        $container.append(buttonsHtml);

        // Create ticket on confirmation
        $container.find('.confirm-yes').on('click', function() {
            $(this).prop('disabled', true).text('Creating...');
            $.ajax({
                url: M.cfg.wwwroot + '/local/helpdesk/chatbot_create_ticket.php',
                method: 'POST',
                data: {
                    subject: proposedTicket.subject,
                    priority: proposedTicket.priority,
                    description: proposedTicket.description,
                    question: originalQuestion,
                    ai_response: proposedTicket.ai_response,   
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        const ticketLink = M.cfg.wwwroot + '/local/helpdesk/view.php?id=' + res.ticketid;
                        const successHtml = `<div class="chat-bubble ai-msg" style="margin-top: 8px;">${res.reply} <a href="${ticketLink}">View ticket</a></div>`;
                        $container.parent().append(successHtml);
                    } else {
                        $container.parent().append('<div class="chat-bubble ai-msg" style="margin-top: 8px;">Error: ' + (res.error || 'Could not create ticket') + '</div>');
                    }
                    $container.find('.confirmation-buttons').remove();
                    scrollToBottom();
                },
                error: function() {
                    $container.parent().append('<div class="chat-bubble ai-msg" style="margin-top: 8px;">Error: Ticket creation failed.</div>');
                    $container.find('.confirmation-buttons').remove();
                    scrollToBottom();
                }
            });
        });

        // Handle "No, thanks"
        $container.find('.confirm-no').on('click', function() {
            $container.find('.confirmation-buttons').remove();
            $container.parent().append('<div class="chat-bubble ai-msg" style="margin-top: 8px;">Okay, no ticket was created. You can ask me something else.</div>');
            scrollToBottom();
        });
    }

    const sendMessage = function() {
        const message = $('#ai-chat-input').val().trim();
        if (!message) return;

        $('#ai-chat-body').append(renderBubble(message, 'user-msg'));
        $('#ai-chat-input').val('');

        const loadingId = 'loading-' + Date.now();
        $('#ai-chat-body').append('<div id="' + loadingId + '" class="typing">AI is thinking...</div>');
        scrollToBottom();

        $.ajax({
            url: M.cfg.wwwroot + '/local/helpdesk/chatbot_ajax.php',
            method: 'POST',
            data: { question: message, sesskey: M.cfg.sesskey },
            dataType: 'json',
            success: function(res) {
                $('#' + loadingId).remove();
                if (res.error) {
                    $('#ai-chat-body').append(renderBubble(res.error, 'ai-msg'));
                    sessionHistory.push({question: message, answer: res.error});
                    speakResponse(res.error);
                    scrollToBottom();
                    return;
                }

                if (res.needs_confirmation) {
                    // Show the proposal message
                    const proposalMsgId = 'proposal-' + Date.now();
                    const $proposalBubble = renderBubble(res.reply, 'ai-msg');
                    $proposalBubble.attr('id', proposalMsgId);
                    $('#ai-chat-body').append($proposalBubble);
                    showConfirmationButtons(proposalMsgId, res.proposed_ticket, message);
                    sessionHistory.push({question: message, answer: res.reply + ' [Awaiting confirmation]'});
                    speakResponse(res.reply);
                } else {
                    const reply = res.reply;
                    $('#ai-chat-body').append(renderBubble(reply, 'ai-msg'));
                    sessionHistory.push({question: message, answer: reply});
                    speakResponse(reply);
                    if (res.escalated && res.ticketid) {
                        const viewUrl = M.cfg.wwwroot + '/local/helpdesk/view.php?id=' + res.ticketid;
                        const linkHtml = '<a href="' + viewUrl + '" class="btn btn-sm btn-primary mt-2">View Ticket</a>';
                        $('#ai-chat-body').append(renderBubble(linkHtml, 'ai-msg'));
                    }
                }
                scrollToBottom();
            },
            error: function() {
                $('#' + loadingId).remove();
                const errMsg = 'Sorry, an error occurred. Please try again later.';
                $('#ai-chat-body').append(renderBubble(errMsg, 'ai-msg'));
                sessionHistory.push({question: message, answer: errMsg});
                scrollToBottom();
            }
        });
    };

    const startVoiceInput = () => {
        if (!micEnabled || !hasSpeechRecognition || recognition) return;

        recognition = new SpeechRecognition();
        recognition.lang = 'en-US';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        $micButton.addClass('listening');

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            const currentText = $('#ai-chat-input').val().trim();
            $('#ai-chat-input').val((currentText ? currentText + ' ' : '') + transcript).focus();
        };

        recognition.onerror = () => {
            $micButton.removeClass('listening');
            recognition = null;
        };

        recognition.onend = () => {
            $micButton.removeClass('listening');
            recognition = null;
        };

        recognition.start();
    };

    // Event bindings
    $voiceToggleBtn.on('click', (e) => {
        e.stopPropagation();
        voiceEnabled = !voiceEnabled;
        updateVoiceToggleUI();
        if (!voiceEnabled && hasSpeechSynthesis) window.speechSynthesis.cancel();
    });

    $micToggleBtn.on('click', (e) => {
        e.stopPropagation();
        micEnabled = !micEnabled;
        updateMicToggleUI();
    });

    $('#ai-chat-icon').on('click', () => {
        $('#ai-chat-window').fadeToggle(200).css('display', 'flex');
        if ($('#ai-chat-body').children().length === 0) loadHistory();
    });

    $('#close-chat').on('click', () => $('#ai-chat-window').fadeOut(200));
    $('#ai-chat-send').on('click', sendMessage);
    $('#ai-chat-mic').on('click', startVoiceInput);
    $('#ai-chat-input').on('keypress', (e) => {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    updateVoiceToggleUI();
    updateMicToggleUI();
});
</script>

<?php
echo $OUTPUT->footer();