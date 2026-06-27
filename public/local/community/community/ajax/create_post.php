<?php
require('../../../config.php');
require_login();

global $DB, $USER;

header('Content-Type: application/json');

use local_community\reputation_manager;

try {

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw);

    if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('Invalid JSON payload');
    }

    if (empty($data->title) || empty($data->content)) {
        throw new moodle_exception('Missing title or content');
    }

    // -------------------------
    // Create post
    // -------------------------
    $post = new stdClass();
    $post->userid      = $USER->id;
    $post->title       = $data->title;
    $post->content     = $data->content;
    $post->posttype    = $data->posttype ?? 'discussion';
    $post->timecreated = time();

    $postid = $DB->insert_record('local_community_posts', $post);

    if (!$postid) {
        throw new moodle_exception('Failed to create post');
    }

    // -------------------------
    // ✅ Reputation (NO EVENTS)
    // -------------------------
    reputation_manager::add_points($USER->id, 5, 'post_created', $postid);

    // -------------------------
    // Notify FastAPI
    // -------------------------
    $post->id = $postid;

    $ch = curl_init("http://127.0.0.1:8000/sync");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($post)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpcode !== 200) {
        debugging("FastAPI sync failed: http=$httpcode curl=$curlerr", DEBUG_DEVELOPER);

        echo json_encode([
            'status'  => 'ok',
            'warning' => 'post_created_but_sync_failed'
        ]);
        exit;
    }

    // -------------------------
    // Success
    // -------------------------
    echo json_encode([
        'status' => 'ok',
        'postid' => $postid
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}