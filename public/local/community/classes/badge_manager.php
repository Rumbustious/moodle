<?php
namespace local_community;

defined('MOODLE_INTERNAL') || die();

use local_community\reputation_manager;

class badge_manager {

    /**
     * Check all badges for a user and return newly earned ones.
     */
    public static function process_user(int $userid): array {
        global $DB;

        $earned = [];

        $badges = $DB->get_records('local_community_badges');

        foreach ($badges as $badge) {

            if (self::already_has_badge($userid, $badge->id)) {
                continue;
            }

            $value = self::get_user_metric($userid, $badge);

            if ($value >= $badge->threshold) {

                self::award_badge($userid, $badge->id);

                $earned[] = [
                    'id'          => $badge->id,
                    'name'        => $badge->name,
                    'description' => $badge->description,
                    'icon'        => $badge->icon
                ];
            }
        }

        return $earned;
    }

    /**
     * Check if user already has badge
     */
    private static function already_has_badge(int $userid, int $badgeid): bool {
        global $DB;

        return $DB->record_exists('local_community_user_badges', [
            'userid'  => $userid,
            'badgeid' => $badgeid
        ]);
    }

    /**
     * Award badge to user
     */
    private static function award_badge(int $userid, int $badgeid): void {
        global $DB;

        $record = (object)[
            'userid'      => $userid,
            'badgeid'     => $badgeid,
            'timecreated' => time()
        ];

        $DB->insert_record('local_community_user_badges', $record);
    }

    /**
     * Get user metric depending on badge rule
     */
    private static function get_user_metric(int $userid, \stdClass $badge): int {
        global $DB;

        switch ($badge->rule) {

            // ✅ QUESTIONS (only real questions)
            case 'questions':
                return $DB->count_records('local_community_posts', [
                    'userid'   => $userid,
                    'posttype' => 'question'
                ]);

            // ✅ ANSWERS
            case 'answers':
                return $DB->count_records('local_community_answers', [
                    'userid' => $userid
                ]);

            // ✅ ANSWER VOTES (dynamic threshold)
            case 'answer_votes':
                return (int)$DB->count_records_sql("
                    SELECT COUNT(1)
                    FROM {local_community_answers}
                    WHERE userid = ?
                    AND votes >= ?
                ", [$userid, $badge->threshold]);

            // ✅ REPUTATION
            case 'reputation':
                return (int) reputation_manager::get_user_reputation($userid);
        }

        return 0;
    }
}