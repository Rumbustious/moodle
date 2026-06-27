<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

global $DB, $USER;

use local_community\badge_manager;

// Get post ID
$postid = required_param('id', PARAM_INT);

// Context for formatting
$context = context_system::instance();

// =============================
// GET POST
// =============================
$post = $DB->get_record_sql("
    SELECT p.id,
           p.userid,
           p.title,
           p.content,
           p.timecreated,
           u.firstname,
           u.lastname,
           COALESCE(SUM(v.value), 0) AS votes,
           MAX(CASE WHEN v.userid = :currentuserid THEN v.value ELSE 0 END) AS uservote
    FROM {local_community_posts} p
    JOIN {user} u ON u.id = p.userid
    LEFT JOIN {local_community_votes} v ON v.postid = p.id
    WHERE p.id = :postid
    GROUP BY p.id, p.userid, p.title, p.content, p.timecreated, u.firstname, u.lastname
", [
    'currentuserid' => $USER->id,
    'postid' => $postid
]);

if (!$post) {
    echo json_encode(['error' => 'Post not found']);
    exit;
}

// Format content
$post->content = format_text($post->content, FORMAT_HTML, ['context' => $context]);

// =============================
// GET REPUTATION AND BADGES FOR POST AUTHOR
// =============================
$post->reputation = (int) $DB->get_field('local_community_reputation', 'points', ['userid' => $post->userid]);

$post->badges = array_values($DB->get_records_sql("
    SELECT b.id, b.name, b.icon
    FROM {local_community_user_badges} ub
    JOIN {local_community_badges} b ON b.id = ub.badgeid
    WHERE ub.userid = ?
", [$post->userid]));

// =============================
// GET ANSWERS
// =============================
$answers = $DB->get_records_sql("
    SELECT a.id,
           a.userid,
           a.content,
           a.timecreated,
           u.firstname,
           u.lastname,
           COALESCE(SUM(v.value), 0) AS votes,
           MAX(CASE WHEN v.userid = :currentuserid THEN v.value ELSE 0 END) AS uservote
    FROM {local_community_answers} a
    JOIN {user} u ON u.id = a.userid
    LEFT JOIN {local_community_votes} v ON v.answerid = a.id
    WHERE a.postid = :postid
    GROUP BY a.id, a.userid, a.content, a.timecreated, u.firstname, u.lastname
    ORDER BY a.timecreated ASC
", [
    'currentuserid' => $USER->id,
    'postid' => $postid
]);

// Add can_delete, format content, reputation & badges for each answer
foreach ($answers as $a) {

    $a->content = format_text($a->content, FORMAT_HTML, ['context' => $context]);

    $a->can_delete =
        ($a->userid == $USER->id) ||   // Owner of answer
        ($post->userid == $USER->id);  // Owner of post

    $a->uservote = (int)$a->uservote;
    $a->votes    = (int)$a->votes;

    // Reputation and badges for answer author
    $a->reputation = (int) $DB->get_field('local_community_reputation', 'points', ['userid' => $a->userid]);

     $a->badges = array_values($DB->get_records_sql("
        SELECT b.id, b.name, b.icon
        FROM {local_community_user_badges} ub
        JOIN {local_community_badges} b ON b.id = ub.badgeid
        WHERE ub.userid = ?
    ", [$a->userid]));
}

// Post vote info
$post->uservote = (int)$post->uservote;
$post->votes    = (int)$post->votes;

// =============================
echo json_encode([
    'post' => $post,
    'answers' => array_values($answers)
]);