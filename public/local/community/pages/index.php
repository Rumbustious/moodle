<?php
require('../../../config.php');
require_login();

// 🔹 Use SYSTEM context for a global page
$context = context_system::instance();

// 🔹 Page setup (ORDER IS IMPORTANT)
$PAGE->set_url('/local/community/pages/index.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/local/community/styles.css');


// 🔹 Use language strings (best practice)
$PAGE->set_title(get_string('pluginname', 'local_community'));
$PAGE->set_heading(get_string('pluginname', 'local_community'));

// 🔹 Optional capability (recommended for production)
// require_capability('local/community:view', $context);

echo $OUTPUT->header();

// 🔹 Root container for your JS app
echo html_writer::div('', 'container mt-4', ['id' => 'community-app']);

// 🔹 Load AMD module
$PAGE->requires->js_call_amd('local_community/community', 'init');

echo $OUTPUT->footer();