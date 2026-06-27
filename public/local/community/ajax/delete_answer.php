<?php
require('../../../config.php');
require_login();
require_sesskey();

global $DB, $USER;

$answerid = required_param('answerid', PARAM_INT);

$answer = $DB->get_record('local_community_answers', ['id' => $answerid], '*', MUST_EXIST);
$post   = $DB->get_record('local_community_posts', ['id' => $answer->postid], '*', MUST_EXIST);

if ($answer->userid != $USER->id && $post->userid != $USER->id) {
    throw new moodle_exception('nopermission', 'error');
}

$DB->delete_records('local_community_votes', ['answerid' => $answerid]);
$DB->delete_records('local_community_answers', ['id' => $answerid]);

echo json_encode(['status' => 'deleted']);