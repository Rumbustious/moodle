<?php
require('../../../config.php');
require_login();
require_sesskey();

use local_community\reputation_manager;

header('Content-Type: application/json');

global $DB, $USER;

try {

    $postid   = optional_param('postid', 0, PARAM_INT);
    $answerid = optional_param('answerid', 0, PARAM_INT);
    $value    = optional_param('value', 0, PARAM_INT);

    if (!$value || (!$postid && !$answerid)) {
        throw new moodle_exception('Invalid vote data');
    }

    // -------------------------
    // Detect target record
    // -------------------------
    if ($postid) {
        $target = $DB->get_record('local_community_posts', ['id' => $postid], '*', MUST_EXIST);
        $ownerid = $target->userid;
        $type = 'post';
        $itemid = $postid;
    }

    if ($answerid) {
        $target = $DB->get_record('local_community_answers', ['id' => $answerid], '*', MUST_EXIST);
        $ownerid = $target->userid;
        $type = 'answer';
        $itemid = $answerid;
    }

    // Prevent self voting
    if ($ownerid == $USER->id) {
        throw new moodle_exception('You cannot vote your own content');
    }

    // -------------------------
    // Existing vote?
    // -------------------------
    $conditions = ['userid' => $USER->id];
    if ($postid)   $conditions['postid']   = $postid;
    if ($answerid) $conditions['answerid'] = $answerid;

    $existing = $DB->get_record('local_community_votes', $conditions);

    $oldvalue = 0;

    if ($existing) {

        $oldvalue = $existing->value;
        $existing->value = $value;
        $DB->update_record('local_community_votes', $existing);

        // Update cached count
        if ($postid) {
            $DB->execute("UPDATE {local_community_posts}
                          SET votes = votes - ? + ?
                          WHERE id = ?", [$oldvalue, $value, $postid]);
        }

        if ($answerid) {
            $DB->execute("UPDATE {local_community_answers}
                          SET votes = votes - ? + ?
                          WHERE id = ?", [$oldvalue, $value, $answerid]);
        }

    } else {

        $vote = (object)[
            'userid'      => $USER->id,
            'postid'      => $postid,
            'answerid'    => $answerid,
            'value'       => $value,
            'timecreated' => time()
        ];

        $DB->insert_record('local_community_votes', $vote);

        if ($postid) {
            $DB->execute("UPDATE {local_community_posts}
                          SET votes = votes + ?
                          WHERE id = ?", [$value, $postid]);
        }

        if ($answerid) {
            $DB->execute("UPDATE {local_community_answers}
                          SET votes = votes + ?
                          WHERE id = ?", [$value, $answerid]);
        }
    }

    // -------------------------
    // ✅ REPUTATION CALCULATION
    // -------------------------
    $reputationdelta = 0;

    // Remove old vote effect
    if ($oldvalue == 1)  $reputationdelta -= 10;
    if ($oldvalue == -1) $reputationdelta += 2;

    // Apply new vote effect
    if ($value == 1)  $reputationdelta += 10;
    if ($value == -1) $reputationdelta -= 2;

    if ($reputationdelta != 0) {
        reputation_manager::add_points(
            $ownerid,
            $reputationdelta,
            "{$type}_voted",
            $itemid
        );

    }

    echo json_encode(['status' => 'ok']);

} catch (Throwable $e) {

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}