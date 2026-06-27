<?php
namespace local_community;

defined('MOODLE_INTERNAL') || die();

use local_community\badge_manager;

class reputation_manager {

    /**
     * Add reputation points for a user and automatically check for badges.
     *
     * @param int $userid
     * @param int $points
     * @param string $reason
     * @param int $itemid
     * @return bool
     */
    public static function add_points(int $userid, int $points, string $reason, int $itemid): bool {
        global $DB;

        // 1️⃣ Insert rep_log entry
        $log = (object)[
            'userid' => $userid,
            'points' => $points,
            'reason' => $reason,
            'itemid' => $itemid,
            'timecreated' => time()
        ];

        $logid = $DB->insert_record('local_community_rep_log', $log);
        if (empty($logid)) {
            debugging("Failed to insert rep_log for userid=$userid", DEBUG_DEVELOPER);
            return false;
        }

        // 2️⃣ Update or insert total reputation
        $existing = $DB->get_record('local_community_reputation', ['userid' => $userid]);
        $time = time();

        if ($existing) {
            $existing->points += $points;
            $existing->timemodified = $time;
            $DB->update_record('local_community_reputation', $existing);
        } else {
            $rep = (object)[
                'userid' => $userid,
                'points' => $points,
                'timecreated' => $time,
                'timemodified' => $time
            ];
            $DB->insert_record('local_community_reputation', $rep);
        }

        // 3️⃣ Check badges using badge_manager
        badge_manager::check_all_badges($userid);

        return true;
    }

    /**
     * Get total reputation for a user.
     *
     * @param int $userid
     * @return int
     */
    public static function get_user_reputation(int $userid): int {
        global $DB;
        return (int) $DB->get_field('local_community_reputation', 'points', ['userid' => $userid]) ?? 0;
    }
}