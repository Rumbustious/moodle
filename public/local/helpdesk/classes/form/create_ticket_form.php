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
 * Ticket creation Moodle form for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_helpdesk\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Create ticket form.
 */
class create_ticket_form extends \moodleform {

    /**
     * Build the form definition.
     */
    public function definition() {
        global $USER, $DB;

        $mform = $this->_form;

        // Display the username (read-only).
        $mform->addElement('static', 'username_display',
            get_string('username'), \html_writer::tag('strong', $USER->username));

        // Hidden: actual userid.
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        // Course selector: enrolled courses + guest option.
        $courses = enrol_get_my_courses(null, 'fullname ASC');
        $courseoptions = [0 => get_string('nocourseguest', 'local_helpdesk')];
        foreach ($courses as $course) {
            $courseoptions[$course->id] = format_string($course->fullname);
        }
        $mform->addElement('select', 'courseid',
            get_string('ticketcourse', 'local_helpdesk'), $courseoptions);
        $mform->setType('courseid', PARAM_INT);
        // Default to the first enrolled course, or 0 if none.
        $mform->setDefault('courseid', !empty($courses) ? array_key_first($courses) : 0);

        // Ticket subject.
        $mform->addElement('text', 'subject',
            get_string('ticketsubject', 'local_helpdesk'), ['maxlength' => 255, 'size' => 60]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        // Ticket description (TinyMCE / default editor).
        $mform->addElement('editor', 'description_editor',
            get_string('ticketdescription', 'local_helpdesk'),
            null,
            ['maxfiles' => 0, 'noclean' => false]);
        $mform->setType('description_editor', PARAM_RAW);

        // Priority selector.
        $priorities = [
            'low'    => get_string('prioritylow',    'local_helpdesk'),
            'medium' => get_string('prioritymedium', 'local_helpdesk'),
            'high'   => get_string('priorityhigh',   'local_helpdesk'),
            'urgent' => get_string('priorityurgent', 'local_helpdesk'),
        ];
        $mform->addElement('select', 'priority',
            get_string('ticketpriority', 'local_helpdesk'), $priorities);
        $mform->setDefault('priority', 'low');

        // Attachments: max 640 MB, max 5 files.
        $mform->addElement('filemanager', 'attachments',
            get_string('attachments', 'local_helpdesk'),
            null,
            [
                'subdirs'        => 0,
                'maxbytes'       => 640 * 1024 * 1024, // 640 MB.
                'maxfiles'       => 5,
                'accepted_types' => '*',
            ]);

        $this->add_action_buttons(true, get_string('createticket', 'local_helpdesk'));
    }

    /**
     * Extra validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB, $USER;

        $errors = parent::validation($data, $files);

        if (empty(trim($data['subject']))) {
            $errors['subject'] = get_string('required');
        }

        // Enforce maximum of 3 open tickets per user.
        $opencount = $DB->count_records_select(
            'local_helpdesk_tickets',
            "userid = :userid AND status IN ('open','inprogress')",
            ['userid' => $USER->id]
        );
        if ($opencount >= 3) {
            $errors['subject'] = get_string('maxopentickets', 'local_helpdesk', $opencount);
        }

        return $errors;
    }
}
