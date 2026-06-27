<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Shared helpers for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_helpdesk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared helpers for local_helpdesk.
 */
class helper {

    /**
     * Get the configured open-ticket limit.
     *
     * @return int
     */
    public static function get_ticket_limit(): int {
        $limit = (int)get_config('local_helpdesk', 'ticketlimit');
        return $limit > 0 ? $limit : 3;
    }

    /**
     * Whether ticket pages should show the assigned-to field.
     *
     * @return bool
     */
    public static function show_assigned_to(): bool {
        $value = get_config('local_helpdesk', 'showassignedto');
        return $value === false ? true : (bool)$value;
    }

    /**
     * Whether the manage page dashboard should be displayed.
     *
     * @return bool
     */
    public static function show_dashboard(): bool {
        $value = get_config('local_helpdesk', 'showdashboard');
        return $value === false ? true : (bool)$value;
    }

    /**
     * Get the technical support role record.
     *
     * @return \stdClass|null
     */
    public static function get_technical_support_role(): ?\stdClass {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'technical_support']);
        return $role ?: null;
    }

    /**
     * Get users assigned to the technical_support role in the system context.
     *
     * @return array
     */
    public static function get_technical_support_users(): array {
        global $DB;

        $role = self::get_technical_support_role();
        if (!$role) {
            return [];
        }

        $context = \context_system::instance();
        $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ra.roleid = :roleid
                   AND ra.contextid = :contextid
                   AND u.deleted = 0
                   AND u.suspended = 0
              ORDER BY u.firstname ASC, u.lastname ASC, u.username ASC";

        return $DB->get_records_sql($sql, [
            'roleid' => $role->id,
            'contextid' => $context->id,
        ]);
    }

    /**
     * Build select options for technical support users.
     *
     * @param int|null $selecteduserid
     * @param bool $includeunassigned
     * @return array
     */
    public static function get_support_user_options(?int $selecteduserid = null, bool $includeunassigned = true): array {
        $options = [];

        if ($includeunassigned) {
            $options[] = [
                'value' => 0,
                'label' => get_string('unassigned', 'local_helpdesk'),
                'selected' => empty($selecteduserid),
            ];
        }

        foreach (self::get_technical_support_users() as $user) {
            $options[] = [
                'value' => $user->id,
                'label' => fullname($user) . ' (' . $user->username . ')',
                'selected' => ((int)$selecteduserid === (int)$user->id),
            ];
        }

        return $options;
    }
}
