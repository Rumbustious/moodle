<?php
// This file is part of Moodle - http://moodle.org/

namespace local_community;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook listener for local_community navigation.
 *
 * @package    local_community
 */
class hook_listener {

    /**
     * Adds Community to the primary navigation.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function extend_primary_navigation(\core\hook\navigation\primary_extend $hook): void {
        if (!isloggedin() || isguestuser()) {
            return;
        }

        $context = \context_system::instance();
        if (!has_capability('local/community:view', $context)) {
            return;
        }

        $hook->get_primaryview()->add(
            get_string('pluginname', 'local_community'),
            new \moodle_url('/local/community/pages/index.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'community',
            new \pix_icon('i/group', '')
        );
    }
}
