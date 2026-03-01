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
 * Web service definitions for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'local_helpdesk_send_message' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'send_message',
        'description'   => 'Send a message in a helpdesk chat session.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_helpdesk_get_messages' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'get_messages',
        'description'   => 'Get messages for a helpdesk chat session.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_helpdesk_get_unread_count' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'get_unread_count',
        'description'   => 'Get unread message count for the current user.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_helpdesk_change_ticket_status' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'change_ticket_status',
        'description'   => 'Change the status of a helpdesk ticket.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_helpdesk_open_chat' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'open_chat',
        'description'   => 'Open a chat session for a helpdesk ticket.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_helpdesk_close_chat' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'close_chat',
        'description'   => 'Close a chat session.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_helpdesk_submit_feedback' => [
        'classname'     => \local_helpdesk\external\api::class,
        'methodname'    => 'submit_feedback',
        'description'   => 'Submit feedback for a resolved helpdesk ticket.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
