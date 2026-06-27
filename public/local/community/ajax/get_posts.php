<?php
require('../../../config.php');
require_login();

global $DB, $USER;

header('Content-Type: application/json');

$q = trim(optional_param('q', '', PARAM_TEXT));
$sort = optional_param('sort', 'recent', PARAM_ALPHA);

$params = [];
$where = '';

if ($q !== '') {
    $where = 'WHERE ' . $DB->sql_like('p.title', ':q', false);
    $params['q'] = '%' . $q . '%';
}

$orderby = 'p.timecreated DESC';
if ($sort === 'votes') {
    $orderby = 'votes DESC, p.timecreated DESC';
} else if ($sort === 'answers') {
    $orderby = 'answers DESC, p.timecreated DESC';
}

$sql = "
SELECT
    p.id,
    p.userid,
    p.title,
    u.firstname,
    u.lastname,
    COALESCE(SUM(v.value), 0) AS votes,
    COUNT(DISTINCT a.id) AS answers,
    COALESCE(r.points, 0) AS reputation
FROM {local_community_posts} p
JOIN {user} u ON u.id = p.userid
LEFT JOIN {local_community_votes} v ON v.postid = p.id
LEFT JOIN {local_community_answers} a ON a.postid = p.id
LEFT JOIN {local_community_reputation} r ON r.userid = p.userid
{$where}
GROUP BY p.id, p.userid, p.title, u.firstname, u.lastname, r.points, p.timecreated
ORDER BY {$orderby}
";

$posts = $DB->get_records_sql($sql, $params, 0, 100);

if (!$posts) {
    echo json_encode([]);
    exit;
}

$postuserids = array_unique(array_column($posts, 'userid'));
list($insql, $badgeparams) = $DB->get_in_or_equal($postuserids);

$badges = $DB->get_records_sql(" 
    SELECT ub.userid, b.name, b.icon
    FROM {local_community_user_badges} ub
    JOIN {local_community_badges} b ON b.id = ub.badgeid
    WHERE ub.userid $insql
", $badgeparams);

$badgesbyuser = [];
foreach ($badges as $badge) {
    $badgesbyuser[$badge->userid][] = $badge;
}

foreach ($posts as $post) {
    $post->badges = $badgesbyuser[$post->userid] ?? [];
    $post->can_delete = ($post->userid == $USER->id) || is_siteadmin();
}

echo json_encode(array_values($posts));