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
 * External API functions for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_helpdesk\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

/**
 * External functions for helpdesk chat and ticket management.
 */
class api extends external_api {

    // -----------------------------------------------------------------------
    // send_message
    // -----------------------------------------------------------------------

    /**
     * Parameters for send_message.
     *
     * @return external_function_parameters
     */
    public static function send_message_parameters(): external_function_parameters {
        return new external_function_parameters([
            'chatid'  => new external_value(PARAM_INT,  'Chat session id'),
            'message' => new external_value(PARAM_TEXT, 'Message text'),
        ]);
    }

    /**
     * Send a message in a chat session.
     *
     * @param int    $chatid
     * @param string $message
     * @return array
     */
    public static function send_message(int $chatid, string $message): array {
        global $DB, $USER;

        ['chatid' => $chatid, 'message' => $message] =
            self::validate_parameters(self::send_message_parameters(), compact('chatid', 'message'));

        $chat = $DB->get_record('local_helpdesk_chats', ['id' => $chatid], '*', MUST_EXIST);
        $ticket = $DB->get_record('local_helpdesk_tickets', ['id' => $chat->ticketid], '*', MUST_EXIST);
        $context = \context_system::instance();
        self::validate_context($context);

        $issupport = has_capability('local/helpdesk:openchat', $context);
        $isowner   = ($ticket->userid == $USER->id);

        if (!$issupport && !$isowner) {
            throw new \moodle_exception('accessdenied', 'local_helpdesk');
        }
        if ($chat->status !== 'open') {
            throw new \moodle_exception('chatclosed', 'local_helpdesk');
        }

        $cleanmessage = clean_param($message, PARAM_TEXT);
        if (trim($cleanmessage) === '') {
            throw new \moodle_exception('invalidmessage', 'local_helpdesk');
        }

        $now = time();
        $record = (object)[
            'chatid'      => $chatid,
            'userid'      => $USER->id,
            'message'     => $cleanmessage,
            'timecreated' => $now,
        ];
        $record->id = $DB->insert_record('local_helpdesk_messages', $record);

        // Send a Moodle notification to the other party.
        if ($isowner) {
            // User sent — notify support (assignedto or all support).
            $touid = $ticket->assignedto ?? null;
        } else {
            // Support sent — notify ticket owner.
            $touid = $ticket->userid;
        }
        if ($touid) {
            $message_obj = new \core\message\message();
            $message_obj->component         = 'local_helpdesk';
            $message_obj->name              = 'newmessage';
            $message_obj->userfrom          = $USER->id;
            $message_obj->userto            = $DB->get_record('user', ['id' => $touid]);
            $message_obj->subject           = get_string('newmessage_subject', 'local_helpdesk');
            $message_obj->fullmessage       = get_string('newmessage', 'local_helpdesk', $ticket->id);
            $message_obj->fullmessageformat = FORMAT_PLAIN;
            $message_obj->fullmessagehtml   = '';
            $message_obj->smallmessage      = get_string('newmessage', 'local_helpdesk', $ticket->id);
            $message_obj->contexturl        = (new \moodle_url('/local/helpdesk/view.php', ['id' => $ticket->id]))->out(false);
            $message_obj->contexturlname    = get_string('viewticket', 'local_helpdesk');
            message_send($message_obj);
        }

        return [
            'id'          => $record->id,
            'timecreated' => $now,
        ];
    }

    /**
     * Return value for send_message.
     *
     * @return external_single_structure
     */
    public static function send_message_returns(): external_single_structure {
        return new external_single_structure([
            'id'          => new external_value(PARAM_INT,  'New message id'),
            'timecreated' => new external_value(PARAM_INT,  'Timestamp'),
        ]);
    }

    // -----------------------------------------------------------------------
    // get_messages
    // -----------------------------------------------------------------------

    /**
     * Parameters for get_messages.
     *
     * @return external_function_parameters
     */
    public static function get_messages_parameters(): external_function_parameters {
        return new external_function_parameters([
            'chatid' => new external_value(PARAM_INT, 'Chat session id'),
            'since'  => new external_value(PARAM_INT, 'Return messages after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Retrieve messages for a chat session, optionally filtered by timestamp.
     *
     * @param int $chatid
     * @param int $since
     * @return array
     */
    public static function get_messages(int $chatid, int $since = 0): array {
        global $DB, $USER;

        ['chatid' => $chatid, 'since' => $since] =
            self::validate_parameters(self::get_messages_parameters(), compact('chatid', 'since'));

        $chat   = $DB->get_record('local_helpdesk_chats',   ['id' => $chatid], '*', MUST_EXIST);
        $ticket = $DB->get_record('local_helpdesk_tickets', ['id' => $chat->ticketid], '*', MUST_EXIST);
        $context = \context_system::instance();
        self::validate_context($context);

        $issupport = has_capability('local/helpdesk:openchat', $context);
        if (!$issupport && $ticket->userid != $USER->id) {
            throw new \moodle_exception('accessdenied', 'local_helpdesk');
        }

        $sql = "SELECT m.*, u.username, u.firstname, u.lastname
                  FROM {local_helpdesk_messages} m
                  JOIN {user} u ON u.id = m.userid
                 WHERE m.chatid = :chatid AND m.timecreated > :since
              ORDER BY m.timecreated ASC";
        $rows = $DB->get_records_sql($sql, ['chatid' => $chatid, 'since' => $since]);

        // Mark messages sent by support as read for the ticket owner and vice-versa.
        $now = time();
        foreach ($rows as $row) {
            if ($row->userid != $USER->id && empty($row->timeread)) {
                $DB->set_field('local_helpdesk_messages', 'timeread', $now, ['id' => $row->id]);
            }
        }

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = [
                'id'          => (int)$row->id,
                'userid'      => (int)$row->userid,
                'username'    => $row->username,
                'fullname'    => fullname($row),
                'message'     => $row->message,
                'timecreated' => (int)$row->timecreated,
                'ismine'      => ($row->userid == $USER->id),
            ];
        }

        return [
            'messages'    => $messages,
            'chatstatus'  => $chat->status,
        ];
    }

    /**
     * Return value for get_messages.
     *
     * @return external_single_structure
     */
    public static function get_messages_returns(): external_single_structure {
        return new external_single_structure([
            'messages'   => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT,  'Message id'),
                    'userid'      => new external_value(PARAM_INT,  'Sender userid'),
                    'username'    => new external_value(PARAM_TEXT, 'Sender username'),
                    'fullname'    => new external_value(PARAM_TEXT, 'Sender full name'),
                    'message'     => new external_value(PARAM_TEXT, 'Message text'),
                    'timecreated' => new external_value(PARAM_INT,  'Timestamp'),
                    'ismine'      => new external_value(PARAM_BOOL, 'Whether current user sent this'),
                ])
            ),
            'chatstatus' => new external_value(PARAM_TEXT, 'Chat status: open or closed'),
        ]);
    }

    // -----------------------------------------------------------------------
    // get_unread_count
    // -----------------------------------------------------------------------

    /**
     * Parameters for get_unread_count.
     *
     * @return external_function_parameters
     */
    public static function get_unread_count_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Return total unread message count for the current user across all helpdesk chats.
     *
     * @return array
     */
    public static function get_unread_count(): array {
        global $DB, $USER;

        self::validate_parameters(self::get_unread_count_parameters(), []);
        $context = \context_system::instance();
        self::validate_context($context);

        if (!isloggedin() || isguestuser()) {
            return ['count' => 0, 'chatid' => 0, 'ticketid' => 0];
        }

        // Find open chats on tickets owned by this user that have unread support messages.
        $sql = "SELECT COUNT(m.id) AS cnt, c.id AS chatid, t.id AS ticketid
                  FROM {local_helpdesk_messages} m
                  JOIN {local_helpdesk_chats} c        ON c.id = m.chatid
                  JOIN {local_helpdesk_tickets} t       ON t.id = c.ticketid
                 WHERE t.userid  = :userid
                   AND m.userid != :userid2
                   AND m.timeread IS NULL
                   AND c.status  = 'open'
              GROUP BY c.id, t.id
              ORDER BY cnt DESC";
        $records = $DB->get_records_sql($sql, ['userid' => $USER->id, 'userid2' => $USER->id], 0, 1);

        if (empty($records)) {
            return ['count' => 0, 'chatid' => 0, 'ticketid' => 0];
        }

        $first = reset($records);
        return ['count' => (int)$first->cnt, 'chatid' => (int)$first->chatid, 'ticketid' => (int)$first->ticketid];
    }

    /**
     * Return value for get_unread_count.
     *
     * @return external_single_structure
     */
    public static function get_unread_count_returns(): external_single_structure {
        return new external_single_structure([
            'count'    => new external_value(PARAM_INT, 'Unread message count'),
            'chatid'   => new external_value(PARAM_INT, 'Chat id with unread messages, or 0'),
            'ticketid' => new external_value(PARAM_INT, 'Ticket id with unread messages, or 0'),
        ]);
    }

    // -----------------------------------------------------------------------
    // change_ticket_status
    // -----------------------------------------------------------------------

    /**
     * Parameters for change_ticket_status.
     *
     * @return external_function_parameters
     */
    public static function change_ticket_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ticketid' => new external_value(PARAM_INT,  'Ticket id'),
            'status'   => new external_value(PARAM_TEXT, 'New status: open, inprogress, resolved, closed'),
        ]);
    }

    /**
     * Change the status of a ticket (technical support only).
     *
     * @param int    $ticketid
     * @param string $status
     * @return array
     */
    public static function change_ticket_status(int $ticketid, string $status): array {
        global $DB, $USER;

        ['ticketid' => $ticketid, 'status' => $status] =
            self::validate_parameters(self::change_ticket_status_parameters(), compact('ticketid', 'status'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/helpdesk:managetickets', $context);

        $allowed = ['open', 'inprogress', 'resolved', 'closed'];
        if (!in_array($status, $allowed)) {
            throw new \moodle_exception('invalidticket', 'local_helpdesk');
        }

        $ticket = $DB->get_record('local_helpdesk_tickets', ['id' => $ticketid], '*', MUST_EXIST);
        $DB->set_field('local_helpdesk_tickets', 'status',       $status, ['id' => $ticketid]);
        $DB->set_field('local_helpdesk_tickets', 'timemodified', time(), ['id' => $ticketid]);

        // Log the action.
        $DB->insert_record('local_helpdesk_ticket_log', (object)[
            'ticketid'    => $ticketid,
            'userid'      => $USER->id,
            'action'      => 'status_changed',
            'detail'      => "Status changed from {$ticket->status} to {$status}",
            'timecreated' => time(),
        ]);

        // Notify the ticket owner.
        $owner = $DB->get_record('user', ['id' => $ticket->userid]);
        if ($owner) {
            $msg = new \core\message\message();
            $msg->component         = 'local_helpdesk';
            $msg->name              = 'statuschanged';
            $msg->userfrom          = $USER->id;
            $msg->userto            = $owner;
            $msg->subject           = get_string('statuschanged_subject', 'local_helpdesk');
            $msg->fullmessage       = get_string('statuschanged', 'local_helpdesk',
                                        get_string('status_' . $status, 'local_helpdesk'));
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml   = '';
            $msg->smallmessage      = get_string('statuschanged', 'local_helpdesk',
                                        get_string('status_' . $status, 'local_helpdesk'));
            $msg->contexturl        = (new \moodle_url('/local/helpdesk/view.php', ['id' => $ticketid]))->out(false);
            $msg->contexturlname    = get_string('viewticket', 'local_helpdesk');
            message_send($msg);
        }

        return ['success' => true];
    }

    /**
     * Return value for change_ticket_status.
     *
     * @return external_single_structure
     */
    public static function change_ticket_status_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True on success'),
        ]);
    }

    // -----------------------------------------------------------------------
    // open_chat
    // -----------------------------------------------------------------------

    /**
     * Parameters for open_chat.
     *
     * @return external_function_parameters
     */
    public static function open_chat_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ticketid' => new external_value(PARAM_INT, 'Ticket id'),
        ]);
    }

    /**
     * Open (or reopen) a chat session for a ticket (technical support only).
     *
     * @param int $ticketid
     * @return array
     */
    public static function open_chat(int $ticketid): array {
        global $DB, $USER;

        ['ticketid' => $ticketid] =
            self::validate_parameters(self::open_chat_parameters(), compact('ticketid'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/helpdesk:openchat', $context);

        $ticket = $DB->get_record('local_helpdesk_tickets', ['id' => $ticketid], '*', MUST_EXIST);

        // Check if there is already an open chat.
        $existing = $DB->get_record('local_helpdesk_chats',
            ['ticketid' => $ticketid, 'status' => 'open']);
        if ($existing) {
            return ['chatid' => (int)$existing->id];
        }

        $now    = time();
        $chatid = $DB->insert_record('local_helpdesk_chats', (object)[
            'ticketid'     => $ticketid,
            'status'       => 'open',
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        // Log the action.
        $DB->insert_record('local_helpdesk_ticket_log', (object)[
            'ticketid'    => $ticketid,
            'userid'      => $USER->id,
            'action'      => 'chat_opened',
            'detail'      => "Chat session #{$chatid} opened",
            'timecreated' => $now,
        ]);

        // Notify ticket owner that chat has started.
        $owner = $DB->get_record('user', ['id' => $ticket->userid]);
        if ($owner) {
            $msg = new \core\message\message();
            $msg->component         = 'local_helpdesk';
            $msg->name              = 'chatstarted';
            $msg->userfrom          = $USER->id;
            $msg->userto            = $owner;
            $msg->subject           = get_string('chatstarted_subject', 'local_helpdesk');
            $msg->fullmessage       = get_string('chatstarted', 'local_helpdesk', $ticketid);
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml   = '';
            $msg->smallmessage      = get_string('chatstarted', 'local_helpdesk', $ticketid);
            $msg->contexturl        = (new \moodle_url('/local/helpdesk/view.php', ['id' => $ticketid]))->out(false);
            $msg->contexturlname    = get_string('viewticket', 'local_helpdesk');
            message_send($msg);
        }

        return ['chatid' => (int)$chatid];
    }

    /**
     * Return value for open_chat.
     *
     * @return external_single_structure
     */
    public static function open_chat_returns(): external_single_structure {
        return new external_single_structure([
            'chatid' => new external_value(PARAM_INT, 'Chat session id'),
        ]);
    }

    // -----------------------------------------------------------------------
    // close_chat
    // -----------------------------------------------------------------------

    /**
     * Parameters for close_chat.
     *
     * @return external_function_parameters
     */
    public static function close_chat_parameters(): external_function_parameters {
        return new external_function_parameters([
            'chatid' => new external_value(PARAM_INT, 'Chat session id'),
        ]);
    }

    /**
     * Close a chat session (technical support only).
     *
     * @param int $chatid
     * @return array
     */
    public static function close_chat(int $chatid): array {
        global $DB, $USER;

        ['chatid' => $chatid] =
            self::validate_parameters(self::close_chat_parameters(), compact('chatid'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/helpdesk:openchat', $context);

        $chat = $DB->get_record('local_helpdesk_chats', ['id' => $chatid], '*', MUST_EXIST);

        $now = time();
        $DB->set_field('local_helpdesk_chats', 'status',       'closed', ['id' => $chatid]);
        $DB->set_field('local_helpdesk_chats', 'timemodified', $now,     ['id' => $chatid]);

        $DB->insert_record('local_helpdesk_ticket_log', (object)[
            'ticketid'    => $chat->ticketid,
            'userid'      => $USER->id,
            'action'      => 'chat_closed',
            'detail'      => "Chat session #{$chatid} closed",
            'timecreated' => $now,
        ]);

        return ['success' => true];
    }

    /**
     * Return value for close_chat.
     *
     * @return external_single_structure
     */
    public static function close_chat_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True on success'),
        ]);
    }

    // -----------------------------------------------------------------------
    // submit_feedback
    // -----------------------------------------------------------------------

    /**
     * Parameters for submit_feedback.
     *
     * @return external_function_parameters
     */
    public static function submit_feedback_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ticketid' => new external_value(PARAM_INT,  'Ticket id'),
            'rating'   => new external_value(PARAM_INT,  'Rating 1-5'),
            'comment'  => new external_value(PARAM_TEXT, 'Optional comment', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Submit feedback for a resolved/closed ticket.
     *
     * @param int    $ticketid
     * @param int    $rating
     * @param string $comment
     * @return array
     */
    public static function submit_feedback(int $ticketid, int $rating, string $comment = ''): array {
        global $DB, $USER;

        ['ticketid' => $ticketid, 'rating' => $rating, 'comment' => $comment] =
            self::validate_parameters(self::submit_feedback_parameters(), compact('ticketid', 'rating', 'comment'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/helpdesk:viewowntickets', $context);

        $ticket = $DB->get_record('local_helpdesk_tickets', ['id' => $ticketid], '*', MUST_EXIST);

        if ($ticket->userid != $USER->id) {
            throw new \moodle_exception('accessdenied', 'local_helpdesk');
        }

        if ($DB->record_exists('local_helpdesk_feedback', ['ticketid' => $ticketid, 'userid' => $USER->id])) {
            throw new \moodle_exception('feedbackalreadygiven', 'local_helpdesk');
        }

        if ($rating < 1 || $rating > 5) {
            $rating = max(1, min(5, $rating));
        }

        $now = time();
        $DB->insert_record('local_helpdesk_feedback', (object)[
            'ticketid'      => $ticketid,
            'userid'        => $USER->id,
            'supportuserid' => $ticket->assignedto ?: null,
            'rating'        => $rating,
            'comment'       => clean_param($comment, PARAM_TEXT),
            'timecreated'   => $now,
        ]);

        $DB->insert_record('local_helpdesk_ticket_log', (object)[
            'ticketid'    => $ticketid,
            'userid'      => $USER->id,
            'action'      => 'feedback_given',
            'detail'      => "Rating: {$rating}",
            'timecreated' => $now,
        ]);

        return ['success' => true];
    }

    /**
     * Return value for submit_feedback.
     *
     * @return external_single_structure
     */
    public static function submit_feedback_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True on success'),
        ]);
    }
}
