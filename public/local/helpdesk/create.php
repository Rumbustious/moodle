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
 * Create a new helpdesk ticket.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/create.php');
$PAGE->set_title(get_string('createticket', 'local_helpdesk'));
$PAGE->set_heading(get_string('createticket', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

// require_capability('local/helpdesk:submitticket', $context);

// Enforce max 3 open tickets.
$opencount = $DB->count_records_select(
    'local_helpdesk_tickets',
    "userid = :userid AND status IN ('open','inprogress')",
    ['userid' => $USER->id]
);
if ($opencount >= 3) {
    redirect(
        new moodle_url('/local/helpdesk/index.php'),
        get_string('maxopentickets', 'local_helpdesk', $opencount),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Prepare a draft area for attachments.
$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area($draftitemid, $context->id, 'local_helpdesk', 'attachments', 0);

// Prepare editor draft area.
$editoropts = ['maxfiles' => 0, 'noclean' => false];
$entry = new stdClass();
$entry->description       = '';
$entry->descriptionformat = FORMAT_HTML;
$entry = file_prepare_standard_editor($entry, 'description', $editoropts, $context,
    'local_helpdesk', 'description', 0);

require_once($CFG->dirroot . '/local/helpdesk/classes/form/create_ticket_form.php');
$form = new \local_helpdesk\form\create_ticket_form();

// Bind draft item IDs so the editor and filemanager use the prepared draft areas.
$form->set_data([
    'description_editor' => $entry->description_editor,
    'attachments'        => $draftitemid,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/helpdesk/index.php'));

} else if ($data = $form->get_data()) {

    // Save the ticket.
    $now = time();

    // Save editor content.
    $data = file_postupdate_standard_editor($data, 'description', $editoropts, $context,
        'local_helpdesk', 'description', 0);

    $ticketid = $DB->insert_record('local_helpdesk_tickets', (object)[
        'userid'            => $USER->id,
        'courseid'          => $data->courseid ?: null,
        'subject'           => clean_param($data->subject, PARAM_TEXT),
        'description'       => $data->description,
        'descriptionformat' => $data->descriptionformat,
        'priority'          => $data->priority,
        'status'            => 'open',
        'timecreated'       => $now,
        'timemodified'      => $now,
    ]);

    // Save attachments.
    file_save_draft_area_files(
        $data->attachments,
        $context->id,
        'local_helpdesk',
        'attachments',
        $ticketid,
        ['subdirs' => 0, 'maxfiles' => 5, 'maxbytes' => 640 * 1024 * 1024]
    );

    // Audit log.
    $DB->insert_record('local_helpdesk_ticket_log', (object)[
        'ticketid'    => $ticketid,
        'userid'      => $USER->id,
        'action'      => 'created',
        'detail'      => "Ticket created with priority {$data->priority}",
        'timecreated' => $now,
    ]);

    // Redirect with toast notification.
    redirect(
        new moodle_url('/local/helpdesk/index.php', ['toast' => 'submitted']),
        get_string('ticketsubmitted', 'local_helpdesk'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
