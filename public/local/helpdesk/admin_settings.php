<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin settings page for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/admin_settings.php');
$PAGE->set_title(get_string('helpdesksettings', 'local_helpdesk'));
$PAGE->set_heading(get_string('helpdesksettings', 'local_helpdesk'));
$PAGE->set_pagelayout('admin');

if (data_submitted() && confirm_sesskey()) {
    $ticketlimit = required_param('ticketlimit', PARAM_INT);
    $ticketlimit = max(1, $ticketlimit);
    $showassignedto = optional_param('showassignedto', 0, PARAM_BOOL);
    $showdashboard = optional_param('showdashboard', 0, PARAM_BOOL);

    set_config('ticketlimit', $ticketlimit, 'local_helpdesk');
    set_config('showassignedto', $showassignedto, 'local_helpdesk');
    set_config('showdashboard', $showdashboard, 'local_helpdesk');

    redirect(
        new moodle_url('/local/helpdesk/admin_settings.php'),
        get_string('settingssaved', 'local_helpdesk'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$ticketlimit = \local_helpdesk\local\helper::get_ticket_limit();
$showassignedto = \local_helpdesk\local\helper::show_assigned_to();
$showdashboard = \local_helpdesk\local\helper::show_dashboard();
$role = \local_helpdesk\local\helper::get_technical_support_role();
$supportusers = \local_helpdesk\local\helper::get_technical_support_users();

$assignurl = null;
if ($role) {
    $assignurl = new moodle_url('/admin/roles/assign.php', [
        'contextid' => $context->id,
        'roleid' => $role->id,
    ]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('helpdesksettings', 'local_helpdesk'));
?>

<form method="post" action="<?php echo $PAGE->url->out(false); ?>" class="mb-4">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

    <div class="form-group">
        <label for="id_ticketlimit"><?php echo get_string('ticketlimit', 'local_helpdesk'); ?></label>
        <input type="number" min="1" id="id_ticketlimit" name="ticketlimit"
               class="form-control" style="max-width: 220px;"
               value="<?php echo s($ticketlimit); ?>">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" id="id_showassignedto" name="showassignedto" value="1"
               class="form-check-input" <?php echo $showassignedto ? 'checked' : ''; ?>>
        <label class="form-check-label" for="id_showassignedto">
            <?php echo get_string('showassignedto', 'local_helpdesk'); ?>
        </label>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" id="id_showdashboard" name="showdashboard" value="1"
               class="form-check-input" <?php echo $showdashboard ? 'checked' : ''; ?>>
        <label class="form-check-label" for="id_showdashboard">
            <?php echo get_string('showdashboard', 'local_helpdesk'); ?>
        </label>
    </div>

    <button type="submit" class="btn btn-primary">
        <?php echo get_string('savechanges', 'core'); ?>
    </button>
    <a href="<?php echo (new moodle_url('/local/helpdesk/manage.php'))->out(false); ?>" class="btn btn-secondary">
        <?php echo get_string('managetickets', 'local_helpdesk'); ?>
    </a>
</form>

<div class="card mb-4">
    <div class="card-header">
        <strong><?php echo get_string('technicalsupportusers', 'local_helpdesk'); ?></strong>
    </div>
    <div class="card-body">
        <?php if ($assignurl): ?>
            <p><?php echo get_string('technicalsupportusers_desc', 'local_helpdesk'); ?></p>
            <a href="<?php echo $assignurl->out(false); ?>" class="btn btn-outline-primary mb-3">
                <?php echo get_string('assigntechnicalsupport', 'local_helpdesk'); ?>
            </a>
        <?php else: ?>
            <div class="alert alert-warning"><?php echo get_string('technicalsupportrolenotfound', 'local_helpdesk'); ?></div>
        <?php endif; ?>

        <?php if ($supportusers): ?>
            <ul class="list-group">
                <?php foreach ($supportusers as $supportuser): ?>
                    <li class="list-group-item">
                        <?php echo fullname($supportuser) . ' (' . s($supportuser->username) . ')'; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-info mb-0"><?php echo get_string('nosupportusers', 'local_helpdesk'); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
