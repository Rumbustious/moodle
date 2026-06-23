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
 * Install script for local_helpdesk.
 * Creates the technical_support role if it does not already exist.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Installation function for local_helpdesk.
 *
 * @return bool
 */
function xmldb_local_helpdesk_install() {
    global $DB;

    // Create the technical_support role if it does not already exist.
    if (!$DB->record_exists('role', ['shortname' => 'technical_support'])) {
        $roleid = create_role(
            'Technical Support',
            'technical_support',
            'Technical support staff who handle helpdesk tickets and chat with users.',
            'user'
        );

        // Assign capabilities to the technical_support role at system context.
        $systemcontext = \context_system::instance();

        $capabilities = [
            'local/helpdesk:viewalltickets',
            'local/helpdesk:managetickets',
            'local/helpdesk:openchat',
            'local/helpdesk:viewowntickets', // ✅ Added missing capability
        ];

        foreach ($capabilities as $cap) {
            assign_capability($cap, CAP_ALLOW, $roleid, $systemcontext->id, true);
        }

        // Allow this role to be assigned at the system context so it appears
        // under Site administration → Users → Permissions → Assign system roles.
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
    }

    return true;
}
