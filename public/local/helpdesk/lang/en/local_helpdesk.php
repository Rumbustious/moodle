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
$string['nologs']                = 'No log entries found.';
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
$string['invalidmessage']        = 'Message cannot be empty.';
$string['accessdenied']          = 'Access denied.';



// Chatbot.
$string['chatbottitle']          = 'Customer Service Chatbot';
$string['chatbotintro']          = 'Ask any question related to this website. If the chatbot cannot answer, it will create a technical support ticket for you.';
$string['chatbotquestionlabel']  = 'Your question';
$string['chatbotaskbutton']      = 'Ask chatbot';
$string['chatbotanswerlabel']    = 'Chatbot response';
$string['chatbotemptyquestion']  = 'Please enter a question before submitting.';
$string['chatbotmaxopen']        = 'I could not answer and cannot create a new ticket because you already have 3 open tickets.';
$string['chatbotticketsubject']  = 'Chatbot escalation: user support request';
$string['chatbotticketdesc']     = '<p><strong>Escalated by chatbot.</strong></p><p>User question:</p><p>{$a}</p>';
$string['chatbotfallbackcreated']= 'I could not answer this question, so I created support ticket #{$a}. A technical support agent will follow up with you soon.';




$string['allassigned'] = 'All tickets';
$string['unassigned'] = 'Unassigned';
$string['assignedtome'] = 'Assigned to me';
$string['allpriorities'] = 'All priorities';
$string['allcourses'] = 'All courses';
$string['allusers'] = 'All users';
$string['allassignedusers'] = 'All support agents';
$string['advancedfilters'] = 'Advanced filters';
$string['searchsubject'] = 'Subject...';
$string['search']= 'Search';
$string['fromdate'] = 'From date';
$string['todate'] = 'To date';
$string['applyfilters'] = 'Apply';
$string['clearfilters'] = 'Clear filters';
$string['assignedto'] = 'Assigned to';



$string['chatbottitle'] = 'AI Assistant';
$string['chatbotquestionlabel'] = 'Ask a question...';
$string['chatbotemptyquestion'] = 'Please enter a question.';
$string['chatbotmaxopen'] = 'You already have 3 open tickets. Please resolve one before creating another.';
$string['chatbotfallbackcreated'] = 'I have created a support ticket (#{$a}) for you. A support agent will follow up soon.';
$string['chatbotintro'] = 'Hello! I\'m your support assistant. Ask me anything about your courses or issues.';


$string['chatbotemptyquestion'] = 'Please enter a question.';
$string['chatbotmaxopen'] = 'You already have 3 open tickets. Please resolve one before creating another.';
$string['chatbotfallbackcreated'] = 'Ticket #{$a} has been created. A support agent will follow up soon.';
$string['chatbotescalationproposal'] = 'It seems this issue requires human support. I can create a ticket for you. Do you want me to do that?';