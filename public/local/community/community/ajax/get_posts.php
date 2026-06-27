<?php
require('../../../config.php');
require_login();

global $DB, $USER;

header('Content-Type: application/json');

$posts = $DB->get_records_sql("
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

GROUP BY
    p.id, p.userid, p.title,
    u.firstname, u.lastname,
    r.points

ORDER BY p.timecreated DESC
");

if (!$posts) {
    echo json_encode([]);
    exit;
}

$postuserids = array_unique(array_column($posts, 'userid'));

list($insql, $params) = $DB->get_in_or_equal($postuserids);

$badges = $DB->get_records_sql("
SELECT ub.userid, b.name, b.icon
FROM {local_community_user_badges} ub
JOIN {local_community_badges} b ON b.id = ub.badgeid
WHERE ub.userid $insql
", $params);

$badgesbyuser = [];

foreach ($badges as $b) {
    $badgesbyuser[$b->userid][] = $b;
}

foreach ($posts as $p) {

    $p->badges = $badgesbyuser[$p->userid] ?? [];

    $p->can_delete =
        ($p->userid == $USER->id) ||
        is_siteadmin();
}

echo json_encode(array_values($posts));