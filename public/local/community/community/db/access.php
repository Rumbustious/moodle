<?php
$capabilities = [

'local/community:post' => [
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => [
        'student' => CAP_ALLOW,
        'teacher' => CAP_ALLOW
    ]
],

'local/community:vote' => [
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => [
        'student' => CAP_ALLOW,
        'teacher' => CAP_ALLOW
    ]
],
'local/community:givereputation' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ]



];