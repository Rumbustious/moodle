<?php

define('AJAX_SCRIPT', true);

require('../../../config.php');
require_login();

global $DB, $PAGE;

$period = optional_param('period', 'all', PARAM_ALPHANUMEXT);

$params = [];
$timefilter = '';

if ($period === '30d') {
    $timefilter = ' AND rl.timecreated >= :mintime';
    $params['mintime'] = time() - (30 * DAYSECS);
} else if ($period === '7d') {
    $timefilter = ' AND rl.timecreated >= :mintime';
    $params['mintime'] = time() - (7 * DAYSECS);
}

$sql = "
SELECT
    u.id AS userid,
    u.firstname,
    u.lastname,
    COALESCE(SUM(rl.points),0) AS points,
    COUNT(DISTINCT p.id) AS posts,
    COUNT(DISTINCT a.id) AS answers
FROM {user} u
LEFT JOIN {local_community_rep_log} rl
    ON rl.userid = u.id {$timefilter}
LEFT JOIN {local_community_posts} p
    ON p.userid = u.id
LEFT JOIN {local_community_answers} a
    ON a.userid = u.id
GROUP BY u.id, u.firstname, u.lastname
HAVING COALESCE(SUM(rl.points),0) > 0
ORDER BY points DESC, answers DESC, posts DESC
";

$leaders = $DB->get_records_sql($sql, $params, 0, 10);

$result = [];

foreach ($leaders as $leader) {

    $user = $DB->get_record(
        'user',
        ['id' => $leader->userid],
        'id,firstname,lastname,picture,imagealt'
    );

    $userpicture = new user_picture($user);
    $userpicture->size = 100;

    $leader->profileimageurl = $userpicture->get_url($PAGE)->out(false);

    $result[] = $leader;
}

echo json_encode($result);
die();