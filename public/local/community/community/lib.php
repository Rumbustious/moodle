<?php
/**
 * Extend global navigation with a link to the community.
 *
 * @param global_navigation $nav Navigation object.
 */
function local_community_extend_navigation(global_navigation $nav) {
    $url = new moodle_url('/local/community/pages/index.php');
    $nav->add(
        get_string('pluginname', 'local_community'), // Link text
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'localcommunity'
    );
}
