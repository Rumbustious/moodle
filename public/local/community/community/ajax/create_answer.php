<?php
require('../../../config.php');
require_login();
require_sesskey();

use local_community\reputation_manager;

header('Content-Type: application/json');

global $DB, $USER;

try {

    $data = json_decode(file_get_contents("php://input"));

    $postid  = isset($data->postid) ? (int)$data->postid : 0;
    $content = isset($data->content) ? clean_param($data->content, PARAM_RAW) : '';

    if ($postid <= 0 || empty($content)) {
        throw new moodle_exception('Invalid input');
    }

    // Ensure post exists
    $post = $DB->get_record('local_community_posts', ['id' => $postid], '*', MUST_EXIST);

    // -------------------------
    // Create answer
    // -------------------------
    $answer = (object)[
        'postid'      => $postid,
        'userid'      => $USER->id,
        'content'     => $content,
        'timecreated' => time(),
        'votes'       => 0
    ];

    $answerid = $DB->insert_record('local_community_answers', $answer);

    // -------------------------
    // ✅ Reputation (direct add_points)
    // -------------------------
    // 5 points per answer, badges checked inside add_points
    reputation_manager::add_points($USER->id, 5, 'answer_created', $answerid);

    echo json_encode([
        'status'   => 'ok',
        'answerid' => $answerid
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}