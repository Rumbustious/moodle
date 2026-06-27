<?php
require('../../../config.php');
// require_login();

header('Content-Type: application/json');

global $DB;

$posts = $DB->get_records('local_community_posts', null, 'timecreated DESC', 'id, title, content');

echo json_encode(array_values($posts));
