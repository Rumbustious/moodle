<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/manage.php');
$PAGE->set_title(get_string('managetickets', 'local_helpdesk'));
$PAGE->set_heading(get_string('managetickets', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

require_capability('local/helpdesk:viewalltickets', $context);

// ================= PAGINATION =================
$perpage = 10;
$page = optional_param('page', 0, PARAM_INT);
$isadmin = is_siteadmin();

// ================= FILTERS =================
$statusfilter       = optional_param('status', '', PARAM_ALPHA);
$priorityfilter     = optional_param('priority', '', PARAM_ALPHA);
$assignmentfilter   = optional_param('assigned', 'all', PARAM_ALPHA);
$createdbyfilter    = optional_param('createdby', 0, PARAM_INT);
$coursefilter       = optional_param('courseid', 0, PARAM_INT);
$assignedtofilter   = optional_param('assignedto', 0, PARAM_INT);
$createdfromstring  = optional_param('createdfrom', '', PARAM_TEXT);
$createdtostring    = optional_param('createdto', '', PARAM_TEXT);
$ticketidfilter     = optional_param('ticketid', 0, PARAM_INT);
$search             = optional_param('search', '', PARAM_TEXT);
$createdfromfilter = 0;
$createdtofilter = 0;

// ================= VALIDATION =================
$validstatuses = ['open', 'inprogress', 'resolved', 'closed'];
if (!in_array($statusfilter, $validstatuses)) $statusfilter = '';

$validpriorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priorityfilter, $validpriorities)) $priorityfilter = '';

$validassignments = ['all', 'unassigned', 'me'];
if (!in_array($assignmentfilter, $validassignments)) $assignmentfilter = 'all';

if ($createdfromstring !== '') {
    $timestamp = strtotime($createdfromstring . ' 00:00:00');
    if ($timestamp !== false) {
        $createdfromfilter = $timestamp;
    }
}
if ($createdtostring !== '') {
    $timestamp = strtotime($createdtostring . ' 23:59:59');
    if ($timestamp !== false) {
        $createdtofilter = $timestamp;
    }
}

// ================= TABS =================
$statustabs = [[
    'label' => get_string('alltickets', 'local_helpdesk'),
    'url' => (new moodle_url('/local/helpdesk/manage.php'))->out(false),
    'selected' => empty($statusfilter),
]];
foreach ($validstatuses as $status) {
    $statustabs[] = [
        'label' => get_string('status_' . $status, 'local_helpdesk'),
        'url' => (new moodle_url('/local/helpdesk/manage.php', ['status' => $status]))->out(false),
        'selected' => ($statusfilter === $status),
    ];
}

$prioritytabs = [[
    'label' => get_string('allpriorities', 'local_helpdesk'),
    'url' => (new moodle_url('/local/helpdesk/manage.php', ['status' => $statusfilter]))->out(false),
    'selected' => empty($priorityfilter),
]];
foreach ($validpriorities as $priority) {
    $prioritytabs[] = [
        'label' => get_string('priority' . $priority, 'local_helpdesk'),
        'url' => (new moodle_url('/local/helpdesk/manage.php', [
            'status' => $statusfilter,
            'priority' => $priority,
        ]))->out(false),
        'selected' => ($priorityfilter === $priority),
    ];
}

$assignmenttabs = [];
foreach ([
    'all' => get_string('allassigned', 'local_helpdesk'),
    'unassigned' => get_string('unassigned', 'local_helpdesk'),
    'me' => get_string('assignedtome', 'local_helpdesk'),
] as $assignment => $label) {
    $assignmenttabs[] = [
        'label' => $label,
        'url' => (new moodle_url('/local/helpdesk/manage.php', [
            'status' => $statusfilter,
            'priority' => $priorityfilter,
            'assigned' => $assignment,
        ]))->out(false),
        'selected' => ($assignmentfilter === $assignment),
    ];
}

// ================= WHERE =================
$where = [];
$params = [];

if ($statusfilter) {
    $where[] = 'status = :status';
    $params['status'] = $statusfilter;
}
if ($priorityfilter) {
    $where[] = 'priority = :priority';
    $params['priority'] = $priorityfilter;
}
if ($assignmentfilter === 'unassigned') {
    $where[] = '(assignedto IS NULL OR assignedto = 0)';
} elseif ($assignmentfilter === 'me') {
    $where[] = 'assignedto = :assignedme';
    $params['assignedme'] = $USER->id;
}
if ($createdbyfilter > 0) {
    $where[] = 'userid = :userid';
    $params['userid'] = $createdbyfilter;
}
if ($coursefilter > 0) {
    $where[] = 'courseid = :courseid';
    $params['courseid'] = $coursefilter;
}
if ($assignedtofilter > 0) {
    $where[] = 'assignedto = :assignedto';
    $params['assignedto'] = $assignedtofilter;
}
// Then later when building WHERE clause:
if ($createdfromfilter > 0) {
    $where[] = 'timecreated >= :createdfrom';
    $params['createdfrom'] = $createdfromfilter;
}
if ($createdtofilter > 0) {
    $where[] = 'timecreated <= :createdto';
    $params['createdto'] = $createdtofilter;
}

if ($ticketidfilter > 0) {
    $where[] = 'id = :id';
    $params['id'] = $ticketidfilter;
}
if (!empty($search)) {
    $where[] = $DB->sql_like('subject', ':search', false);
    $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
}

$whereclause = empty($where) ? '1=1' : implode(' AND ', $where);

// ================= COUNT =================
$totalcount = $DB->count_records_select('local_helpdesk_tickets', $whereclause, $params);

// ================= DASHBOARD =================
$showdashboard = \local_helpdesk\local\helper::show_dashboard();
$dashboardtiles = [];
if ($showdashboard) {
    $dashboardtiles = [
        [
            'label' => get_string('dashboard_open', 'local_helpdesk'),
            'value' => $DB->count_records('local_helpdesk_tickets', ['status' => 'open']),
            'class' => 'info',
            'icon' => 'fa-folder-open',
        ],
        [
            'label' => get_string('dashboard_unanswered', 'local_helpdesk'),
            'value' => $DB->count_records_sql("
                SELECT COUNT(1)
                  FROM {local_helpdesk_tickets} t
                 WHERE t.status IN ('open', 'inprogress')
                   AND NOT EXISTS (
                       SELECT 1
                         FROM {local_helpdesk_chats} c
                         JOIN {local_helpdesk_messages} m ON m.chatid = c.id
                        WHERE c.ticketid = t.id
                          AND m.userid <> t.userid
                   )
            "),
            'class' => 'warning',
            'icon' => 'fa-question-circle',
        ],
        [
            'label' => get_string('dashboard_completed', 'local_helpdesk'),
            'value' => $DB->count_records_select(
                'local_helpdesk_tickets',
                "status IN ('resolved', 'closed')"
            ),
            'class' => 'success',
            'icon' => 'fa-check-circle',
        ],
        [
            'label' => get_string('dashboard_urgent', 'local_helpdesk'),
            'value' => $DB->count_records('local_helpdesk_tickets', ['priority' => 'urgent']),
            'class' => 'danger',
            'icon' => 'fa-exclamation-triangle',
        ],
    ];
}

// ================= FETCH =================
$tickets = $DB->get_records_select(
    'local_helpdesk_tickets',
    $whereclause,
    $params,
    'timecreated DESC',
    '*',
    $page * $perpage,
    $perpage
);

// ================= ROWS =================
$ticketrows = [];
foreach ($tickets as $t) {

    $owner = $DB->get_record('user', ['id' => $t->userid], 'firstname,lastname,username');

    $coursename = get_string('nocourseguest', 'local_helpdesk');
    if ($t->courseid) {
        $course = $DB->get_record('course', ['id' => $t->courseid], 'fullname');
        if ($course) $coursename = format_string($course->fullname);
    }

    $ticketrows[] = [
        'id' => $t->id,
        'subject' => format_string($t->subject),
        'ownername' => $owner ? fullname($owner) : '?',
        'ownerusername' => $owner->username ?? '',
        'coursename' => $coursename,
        'priority' => get_string('priority' . $t->priority, 'local_helpdesk'),
        'prioritykey' => $t->priority,
        'status' => get_string('status_' . $t->status, 'local_helpdesk'),
        'statuskey' => $t->status,
        'timecreated' => userdate($t->timecreated),
        'viewurl' => (new moodle_url('/local/helpdesk/view.php', ['id' => $t->id]))->out(false),
    ];
}

// ================= DISTINCT VALUES =================
$distinctcourses = $DB->get_records_sql_menu("
    SELECT DISTINCT courseid, courseid
    FROM {local_helpdesk_tickets}
    WHERE courseid > 0
");

$distinctusers = $DB->get_records_sql_menu("
    SELECT DISTINCT userid, userid
    FROM {local_helpdesk_tickets}
");

$distinctassigned = $DB->get_records_sql_menu("
    SELECT DISTINCT assignedto, assignedto
    FROM {local_helpdesk_tickets}
    WHERE assignedto > 0
");

// ================= COURSE OPTIONS =================
$courseoptions = [[
    'value' => 0,
    'label' => get_string('allcourses', 'local_helpdesk'),
    'selected' => ($coursefilter == 0)
]];

if ($distinctcourses) {
    list($insql, $inparams) = $DB->get_in_or_equal(array_keys($distinctcourses));
    $courses = $DB->get_records_select('course', "id $insql", $inparams, '', 'id,fullname');

    foreach ($courses as $c) {
        $courseoptions[] = [
            'value' => $c->id,
            'label' => format_string($c->fullname),
            'selected' => ($coursefilter == $c->id)
        ];
    }
}

// ================= USER OPTIONS =================
$useroptions = [[
    'value' => 0,
    'label' => get_string('allusers', 'local_helpdesk'),
    'selected' => ($createdbyfilter == 0)
]];

if ($distinctusers) {
    list($insql, $inparams) = $DB->get_in_or_equal(array_keys($distinctusers));
    $users = $DB->get_records_select('user', "id $insql", $inparams, '', 'id,firstname,lastname,username');

    foreach ($users as $u) {
        $useroptions[] = [
            'value' => $u->id,
            'label' => fullname($u) . " ({$u->username})",
            'selected' => ($createdbyfilter == $u->id)
        ];
    }
}

// ================= ASSIGNED OPTIONS =================
$assignedoptions = [[
    'value' => 0,
    'label' => get_string('allassignedusers', 'local_helpdesk'),
    'selected' => ($assignedtofilter == 0)
]];

if ($distinctassigned) {
    list($insql, $inparams) = $DB->get_in_or_equal(array_keys($distinctassigned));
    $users = $DB->get_records_select('user', "id $insql", $inparams, '', 'id,firstname,lastname,username');

    foreach ($users as $u) {
        $assignedoptions[] = [
            'value' => $u->id,
            'label' => fullname($u) . " ({$u->username})",
            'selected' => ($assignedtofilter == $u->id)
        ];
    }
}

// ================= TEMPLATE =================
$templatedata = [
    'tickets' => $ticketrows,
    'notickets' => empty($ticketrows),
    'showdashboard' => $showdashboard,
    'dashboardtiles' => $dashboardtiles,

    'courseoptions' => $courseoptions,
    'useroptions' => $useroptions,
    'assignedoptions' => $assignedoptions,

    'search' => $search,
    'courseid' => $coursefilter,
    'createdby' => $createdbyfilter,
    'assignedto' => $assignedtofilter,
    'ticketid' => $ticketidfilter,
    'createdfrom' => $createdfromstring,
    'createdto' => $createdtostring,

    'page' => $page,
    'perpage' => $perpage,
    'totalcount' => $totalcount,

    'isadmin' => $isadmin,
    'adminlogurl' => (new moodle_url('/local/helpdesk/admin_log.php'))->out(false),
    'settingsurl' => (new moodle_url('/local/helpdesk/admin_settings.php'))->out(false),
    'exporturl' => (new moodle_url('/local/helpdesk/export.php'))->out(false),
    'filterformurl' => (new moodle_url('/local/helpdesk/manage.php'))->out(false),
    'statustabs' => $statustabs,
    'prioritytabs' => $prioritytabs,
    'assignmenttabs' => $assignmenttabs,
    'communityurl' => (new moodle_url('/local/community/pages/index.php'))->out(false),
    'communityavailable' => file_exists($CFG->dirroot . '/local/community/version.php'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_helpdesk/manage_tickets', $templatedata);
echo $OUTPUT->footer();
