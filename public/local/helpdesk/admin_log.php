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
 * Admin audit log for all helpdesk ticket actions.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/admin_log.php');
$PAGE->set_title(get_string('adminlog', 'local_helpdesk'));
$PAGE->set_heading(get_string('adminlog', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

require_capability('local/helpdesk:viewlog', $context);

// Optional filter by ticket id.
$ticketid = optional_param('ticketid', 0, PARAM_INT);

$params = [];
$where  = '1=1';
if ($ticketid) {
    $where          = 'l.ticketid = :ticketid';
    $params['ticketid'] = $ticketid;
}

$sql = "SELECT l.*, u.firstname, u.lastname, u.username,
               t.subject AS ticketsubject
          FROM {local_helpdesk_ticket_log} l
          JOIN {user} u ON u.id = l.userid
          JOIN {local_helpdesk_tickets} t ON t.id = l.ticketid
         WHERE {$where}
      ORDER BY l.timecreated DESC";

$logs    = $DB->get_records_sql($sql, $params);
$logrows = [];
foreach ($logs as $log) {
    $logrows[] = [
        'ticketid'      => $log->ticketid,
        'ticketsubject' => format_string($log->ticketsubject),
        'ticketurl'     => (new moodle_url('/local/helpdesk/view.php', ['id' => $log->ticketid]))->out(false),
        'username'      => $log->username,
        'fullname'      => fullname($log),
        'action'        => $log->action,
        'detail'        => $log->detail,
        'timecreated'   => userdate($log->timecreated),
    ];
}

// Ticket list for the filter dropdown.
$alltickets = $DB->get_records('local_helpdesk_tickets', null, 'id DESC', 'id, subject');
$ticketfilters = [];
foreach ($alltickets as $t) {
    $ticketfilters[] = [
        'id'       => $t->id,
        'subject'  => format_string($t->subject),
        'selected' => ($t->id == $ticketid),
    ];
}

$templatedata = [
    'logs'          => $logrows,
    'nologs'        => empty($logrows),
    'ticketfilters' => $ticketfilters,
    'filterticketid'=> $ticketid,
    'manageurl'     => (new moodle_url('/local/helpdesk/manage.php'))->out(false),
    'filterurl'     => (new moodle_url('/local/helpdesk/admin_log.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_helpdesk/admin_log', $templatedata);
echo $OUTPUT->footer();
