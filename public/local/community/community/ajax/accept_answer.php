<?php
require('../../../config.php');
require_login();

global $DB, $USER;

$data = json_decode(file_get_contents("php://input"));

$answer = $DB->get_record('local_community_answers', ['id' => $data->answerid], '*', MUST_EXIST);
$post   = $DB->get_record('local_community_posts', ['id' => $answer->postid], '*', MUST_EXIST);

if ($post->userid != $USER->id) {
    throw new moodle_exception('nopermission');
}

$answer->isaccepted = 1;
$DB->update_record('local_community_answers', $answer);

/* Reputation reward */
$DB->execute("
UPDATE {local_community_userrep}
SET reputation = reputation + 15
WHERE userid = ?
", [$answer->userid]);

echo json_encode(['status' => 'accepted']);