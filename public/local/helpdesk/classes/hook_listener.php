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
 * Hook listener for local_helpdesk - adds Helpdesk to primary navigation.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_helpdesk;

/**
 * Hook listener class for adding the Helpdesk navigation node.
 */
class hook_listener {

    /**
     * Extends the primary navigation to add a Helpdesk tab.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function extend_primary_navigation(\core\hook\navigation\primary_extend $hook): void {
        global $CFG;

        // Only show Helpdesk tab to logged-in, non-guest users.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        $primaryview = $hook->get_primaryview();
        $url = new \moodle_url('/local/helpdesk/index.php');
        $primaryview->add(
            get_string('helpdesk', 'local_helpdesk'),
            $url,
            \navigation_node::TYPE_CUSTOM,
            null,
            'helpdesk',
            new \pix_icon('i/help', '')
        );
    }
}
