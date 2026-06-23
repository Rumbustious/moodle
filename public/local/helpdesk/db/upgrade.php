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
 * Upgrade script for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_helpdesk plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_helpdesk_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026030101) {
        // Make local_helpdesk_feedback.supportuserid nullable so feedback can be
        // submitted even when a ticket has no assigned support user.
        //
        // Moodle creates an index for every foreign key definition. That index
        // blocks the column alteration, so we must drop the FK/index first,
        // change the field, then restore the FK.

        $table = new xmldb_table('local_helpdesk_feedback');
        $key   = new xmldb_key('supportuserid', XMLDB_KEY_FOREIGN, ['supportuserid'], 'user', ['id']);
        $field = new xmldb_field('supportuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');

        // Drop the FK (and its auto-generated index) if it exists.
        if ($dbman->find_key_name($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // Change the column to nullable.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        // Restore the foreign key (recreates the index too).
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2026030101, 'local', 'helpdesk');
    }

    if ($oldversion < 2026030102) {
        // The technical_support role was created without a system context level entry,
        // so it did not appear in "Assign system roles". Fix it for existing installs.
        $role = $DB->get_record('role', ['shortname' => 'technical_support']);
        if ($role) {
            set_role_contextlevels($role->id, [CONTEXT_SYSTEM]);
        }

        upgrade_plugin_savepoint(true, 2026030102, 'local', 'helpdesk');
    }

    if ($oldversion < 2026030103) {
        // Re-assign all required capabilities to the technical_support role.
        // The original install.php assign_capability() calls can be silently skipped
        // by Moodle when capability definitions have not yet been fully committed at
        // install time, leaving the role with no effective permissions.
        $role = $DB->get_record('role', ['shortname' => 'technical_support']);
        if ($role) {
            $systemcontext = context_system::instance();

            $caps = [
                'local/helpdesk:viewalltickets',
                'local/helpdesk:managetickets',
                'local/helpdesk:openchat',
            ];

            foreach ($caps as $cap) {
                // Unassign first to ensure a clean state, then re-assign.
                unassign_capability($cap, $role->id, $systemcontext->id);
                assign_capability($cap, CAP_ALLOW, $role->id, $systemcontext->id, true);
            }

            // Flush all cached capability data so the change is visible immediately
            // without requiring a manual cache purge or user re-login.
            accesslib_clear_all_caches(true);
        }

        upgrade_plugin_savepoint(true, 2026030103, 'local', 'helpdesk');
    }

    // New version: Create FAQ Table and Insert Initial Data.
    if ($oldversion < 2026030106) {

        // Define table local_helpdesk_faq.
        $table = new xmldb_table('local_helpdesk_faq');

        // Adding fields to table local_helpdesk_faq.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('answer', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('keywords', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table local_helpdesk_faq.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Initial Data Seeding.
        $faqdata = [
            ['q' => 'How do I log in or reset my password?', 'k' => ['login', 'sign in', 'password', 'reset password'], 'a' => 'If you cannot log in, use "Forgotten your username or password?" on the login page. If you still cannot access your account, I can create a ticket for technical support.'],
            ['q' => 'How do I enrol in a course?', 'k' => ['enrol', 'enroll', 'course access', 'join course'], 'a' => 'To join a course, open the course catalog and select the course. If an enrolment key is required, ask your instructor for the key.'],
            ['q' => 'How do I submit an assignment?', 'k' => ['assignment', 'submit', 'upload'], 'a' => 'Open your course, go to the assignment activity, and click "Add submission". Upload your file and confirm submission before the deadline.'],
            ['q' => 'Where can I see my grades?', 'k' => ['grade', 'result', 'marks'], 'a' => 'You can view your grades from your profile menu or by opening the course gradebook. Some grades appear only after teachers release them.'],
            ['q' => 'Why is the site slow or not loading?', 'k' => ['browser', 'cache', 'slow', 'not loading'], 'a' => 'Please try clearing your browser cache, then reload the page. If the issue remains, test in another browser and I can raise a ticket with your details.'],
            ['q' => 'Is there a mobile app?', 'k' => ['mobile', 'app'], 'a' => 'You can use the Moodle mobile app by selecting this site URL and signing in with your normal account credentials.'],
            ['q' => 'How do I contact support?', 'k' => ['contact', 'support', 'help'], 'a' => 'You are already in Helpdesk. Ask me your issue and if I cannot solve it, I will automatically create a ticket for technical support.'],
        ];

        foreach ($faqdata as $data) {
            $record = new stdClass();
            $record->question = $data['q'];
            $record->answer   = $data['a'];
            $record->keywords = implode(', ', $data['k']); // Convert array to string for DB.
            $DB->insert_record('local_helpdesk_faq', $record);
        }

        upgrade_plugin_savepoint(true, 2026030106, 'local', 'helpdesk');
    }

    return true;
}
