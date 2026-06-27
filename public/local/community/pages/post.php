<?php
/**
 * Post details page.
 *
 * @package    local_community
 * @copyright  2026 Youssef Khaled
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

// -----------------------------
// Set page context & layout
// -----------------------------
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/community/pages/post.php', ['id' => $id]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Post'); // or get_string('post', 'local_community')
// $PAGE->set_heading(get_string('communitypost', 'local_community'));

echo $OUTPUT->header();

// 1️⃣ Container for the Question and Answers (Filled by JS)
echo '<div id="post-data-container" class="mb-5">
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
      </div>';

// 2️⃣ The Answer Form
echo '<div class="card border-0 shadow-sm bg-light">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3">Your Answer</h5>
            <div class="form-group mb-3">
                <textarea id="answercontent" name="answercontent" class="form-control"></textarea>
            </div>
            <div class="d-flex justify-content-end">
                <button id="addanswer" class="btn btn-primary rounded-pill px-4">
                    <i class="fa fa-paper-plane me-2"></i>Post Your Answer
                </button>
            </div>
        </div>
      </div>';

// Initialize Moodle Editor
$editoroptions = [
    'maxfiles' => 0,
    'context' => $PAGE->context,
    'subdirs' => 0,
];
$editor = editors_get_preferred_editor();
$editor->use_editor('answercontent', $editoroptions);

// 3️⃣ Load AMD Module
$PAGE->requires->js_call_amd('local_community/post', 'init', [$id]);

echo $OUTPUT->footer();