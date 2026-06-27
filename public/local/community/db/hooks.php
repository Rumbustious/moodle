<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\navigation\primary_extend::class,
        'callback' => \local_community\hook_listener::class . '::extend_primary_navigation',
        'priority' => 510,
    ],
];
