<?php
/**
 * Upgrade script for local_community plugin.
 * @package    local_community
 */
function xmldb_local_community_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // =============================
    // 1️⃣ Add votes field to posts
    // =============================
    if ($oldversion < 2026022401) {
        $table = new xmldb_table('local_community_posts');
        $field = new xmldb_field('votes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026022401, 'local', 'community');
    }

    // =============================
    // 2️⃣ Add votes field to answers
    // =============================
    if ($oldversion < 2026022402) {
        $table = new xmldb_table('local_community_answers');
        $field = new xmldb_field('votes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026022402, 'local', 'community');
    }

    // =============================
    // 3️⃣ Reputation table
    // =============================
    if ($oldversion < 2026022403) {
        $table = new xmldb_table('local_community_reputation');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('points', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid_unique', XMLDB_KEY_UNIQUE, ['userid']);

            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026022403, 'local', 'community');
    }

    // ... [Versions 4, 5, 6 skipped for brevity, but kept in logic] ...

    // =============================
    // 7️⃣ Reputation log table
    // =============================
    if ($oldversion < 2026022407) {
        $table = new xmldb_table('local_community_rep_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('points', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('reason', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
            $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026022407, 'local', 'community');
    }

    // =============================
    // 8️⃣ Add missing timestamps to reputation table
    // =============================
    if ($oldversion < 2026022408) {
        $table = new xmldb_table('local_community_reputation');

        // Adding timecreated.
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'points');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Adding timemodified.
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026022408, 'local', 'community');
    }

    // =============================
    // 9️⃣ Vote tracking table (to prevent double voting)
    // =============================
    if ($oldversion < 2026022409) {
        $table = new xmldb_table('local_community_votes');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('itemtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL); // 'post' or 'answer'
            $table->add_field('vote', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1); // 1 or -1
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // Ensures a user can only vote once per specific item.
            $table->add_key('user_item_unique', XMLDB_KEY_UNIQUE, ['userid', 'itemid', 'itemtype']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026022409, 'local', 'community');
    }

    return true;
}