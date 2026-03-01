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
 * Language strings for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Helpdesk';

// Navigation.
$string['helpdesk']              = 'Helpdesk';
$string['mytickets']             = 'My Tickets';
$string['alltickets']            = 'All Tickets';
$string['adminlog']              = 'Ticket Log';

// Ticket form.
$string['createticket']          = 'Create Ticket';
$string['ticketsubject']         = 'Ticket Subject';
$string['ticketdescription']     = 'Description';
$string['ticketpriority']        = 'Priority';
$string['ticketcourse']          = 'Related Course';
$string['attachments']           = 'Attachments';
$string['prioritylow']           = 'Low';
$string['prioritymedium']        = 'Medium';
$string['priorityhigh']          = 'High';
$string['priorityurgent']        = 'Urgent';
$string['nocourseguest']         = 'Guest (no course)';
$string['ticketsubmitted']       = 'Your ticket has been submitted successfully.';
$string['ticketsubmitted_title'] = 'Ticket Submitted';
$string['maxopentickets']        = 'You already have {$a} open tickets. You can have a maximum of 3 open tickets at a time.';

// Ticket status.
$string['status']                = 'Status';
$string['status_open']           = 'Open';
$string['status_inprogress']     = 'In Progress';
$string['status_resolved']       = 'Resolved';
$string['status_closed']         = 'Closed';

// Ticket list.
$string['ticketid']              = 'Ticket #';
$string['ticketlist']            = 'Ticket List';
$string['nocourseenrolled']      = 'guest';
$string['notickets']             = 'No tickets found.';
$string['viewticket']            = 'View Ticket';
$string['ticketdetails']         = 'Ticket Details';
$string['createdby']             = 'Created by';
$string['createdon']             = 'Created on';
$string['assignedto']            = 'Assigned To';
$string['unassigned']            = 'Unassigned';
$string['course']                = 'Course';

// Chat.
$string['chat']                  = 'Chat';
$string['openchat']              = 'Open Chat';
$string['closechat']             = 'Close Chat';
$string['chatstarted']           = 'A support representative has started a chat for ticket #{$a}';
$string['chatstarted_subject']   = 'New Support Chat Started';
$string['newmessage']            = 'New message from support for ticket #{$a}';
$string['newmessage_subject']    = 'New Helpdesk Message';
$string['sendmessage']           = 'Send';
$string['typemessage']           = 'Type a message…';
$string['unreadmessages']        = '{$a} unread message(s)';
$string['chatclosed']            = 'This chat session has been closed.';
$string['nochat']                = 'No chat has been started for this ticket yet.';

// Feedback.
$string['feedback']              = 'Feedback';
$string['leavefeedback']         = 'Leave Feedback';
$string['feedbackrating']        = 'Rating';
$string['feedbackcomment']       = 'Comment';
$string['feedbacksubmitted']     = 'Thank you for your feedback!';
$string['feedbackalreadygiven']  = 'You have already submitted feedback for this ticket.';
$string['feedbackoptional']      = '(optional)';

// Manage / technical support.
$string['managetickets']         = 'Manage Tickets';
$string['changestatus']          = 'Change Status';
$string['statuschanged']         = 'Ticket status changed to {$a}';
$string['statuschanged_subject'] = 'Helpdesk Ticket Status Update';
$string['assignticket']          = 'Assign to me';
$string['actions']               = 'Actions';

// Admin log.
$string['ticketlog']             = 'Ticket Action Log';
$string['logaction']             = 'Action';
$string['logdetail']             = 'Detail';
$string['logtime']               = 'Time';
$string['loguser']               = 'User';

// Roles.
$string['role_techsupport']      = 'Technical Support';
$string['role_techsupport_desc'] = 'Technical support staff who handle helpdesk tickets and chat with users.';

// Capabilities.
$string['local/helpdesk:submitticket']   = 'Submit a helpdesk ticket';
$string['local/helpdesk:viewowntickets'] = 'View own helpdesk tickets';
$string['local/helpdesk:viewalltickets'] = 'View all helpdesk tickets';
$string['local/helpdesk:managetickets']  = 'Manage helpdesk tickets';
$string['local/helpdesk:openchat']       = 'Open helpdesk chat sessions';
$string['local/helpdesk:viewlog']        = 'View helpdesk ticket audit log';

// Errors.
$string['invalidticket']         = 'Invalid ticket.';
$string['invalidchat']           = 'Invalid chat session.';
$string['accessdenied']          = 'Access denied.';
