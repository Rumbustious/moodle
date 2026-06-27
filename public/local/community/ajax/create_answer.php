<?php
/**
 * AJAX endpoint to create a new answer in the community.
 *
 * @package    local_community
 * @copyright  2026 Youssef Khaled
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

// Ensure user is logged in.
require_login();

header('Content-Type: application/json');

global $DB, $USER;

use local_community\reputation_manager;

try {
    // 1. Get and decode the JSON payload.
    $raw = file_get_contents("php://input");
    $data = json_decode($raw);

    if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalidjson', 'error');
    }

    // 2. Validate Session Key (CSRF Protection).
    // We use confirm_sesskey manually because require_sesskey() only checks $_POST/$_GET.
    if (empty($data->sesskey) || !confirm_sesskey($data->sesskey)) {
        throw new moodle_exception('invalidsesskey', 'error');
    }

    // 3. Extract and sanitize inputs.
    $postid  = isset($data->postid) ? (int)$data->postid : 0;
    $content = isset($data->content) ? clean_param($data->content, PARAM_RAW) : '';

    if ($postid <= 0 || empty(trim($content))) {
        throw new moodle_exception('missingcontent', 'local_community');
    }

    // 4. Verify Context and Capability.
    $context = context_system::instance();
    require_capability('local/community:post', $context);

    // 5. Ensure the parent post actually exists.
    $post = $DB->get_record('local_community_posts', ['id' => $postid], '*', MUST_EXIST);

    // 6. Create the answer record.
    $answer = new stdClass();
    $answer->postid      = $postid;
    $answer->userid      = $USER->id;
    $answer->content     = $content;
    $answer->timecreated = time();
    $answer->votes       = 0;

    $answerid = $DB->insert_record('local_community_answers', $answer);

    if (!$answerid) {
        throw new moodle_exception('errorinsert', 'local_community');
    }

    // 7. Update Reputation.
    // Award 5 points for providing an answer.
    reputation_manager::add_points($USER->id, 5, 'answer_created', $answerid);

    // 8. Return success response.
    echo json_encode([
        'status'   => 'ok',
        'answerid' => $answerid
    ]);

} catch (Throwable $e) {
    // Handle any errors gracefully for the frontend.
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}