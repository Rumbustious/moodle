<?php
require('../../../config.php');
require_login();
require_sesskey();

global $DB, $USER;

$postid = required_param('postid', PARAM_INT);

$post = $DB->get_record('local_community_posts', ['id' => $postid], '*', MUST_EXIST);

// الصلاحيات
if ($post->userid != $USER->id && !is_siteadmin()) {
    echo json_encode(['status' => 'error', 'message' => 'No permission']);
    exit;
}

$transaction = $DB->start_delegated_transaction();

try {

    // حذف votes الخاصة بالإجابات
    $DB->execute("
        DELETE FROM {local_community_votes}
        WHERE answerid IN (
            SELECT id FROM {local_community_answers}
            WHERE postid = ?
        )
    ", [$postid]);

    // حذف الإجابات
    $DB->delete_records('local_community_answers', ['postid' => $postid]);

    // حذف votes الخاصة بالبوست
    $DB->delete_records('local_community_votes', ['postid' => $postid]);

    // حذف البوست
    $DB->delete_records('local_community_posts', ['id' => $postid]);

    $transaction->allow_commit();

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {

    $transaction->rollback($e);

    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}