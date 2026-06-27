<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\local_community\event\vote_created',
        'callback'  => '\local_community\observer::vote_created',
    ],
    [
        'eventname' => '\local_community\event\answer_created',
        'callback'  => '\local_community\observer::answer_created',
    ],
    [
        'eventname' => '\local_community\event\post_created',
        'callback'  => '\local_community\observer::post_created',
    ],
];
