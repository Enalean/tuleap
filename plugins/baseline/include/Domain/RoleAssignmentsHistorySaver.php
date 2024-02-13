<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Domain;

final class RoleAssignmentsHistorySaver
{
    private const PERM_RESET_FOR_BASELINE_READERS          = 'perm_reset_for_baseline_readers';
    private const PERM_RESET_FOR_BASELINE_ADMINISTRATORS   = 'perm_reset_for_baseline_administrators';
    private const PERM_GRANTED_FOR_BASELINE_READERS        = 'perm_granted_for_baseline_readers';
    private const PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS = 'perm_granted_for_baseline_administrators';
    private const ALL_BASELINE_PERMS_REMOVED_FOR_UGROUP    = 'all_baseline_perms_removed_for_ugroup';

    public function __construct(private readonly AddRoleAssignmentsHistoryEntry $history_entry_adder)
    {
    }

    public static function getLabelFromKey(string $key): ?string
    {
        return match ($key) {
            self::PERM_RESET_FOR_BASELINE_READERS => dgettext(
                'tuleap-baseline',
                'Permission reset for baseline readers'
            ),
            self::PERM_RESET_FOR_BASELINE_ADMINISTRATORS => dgettext(
                'tuleap-baseline',
                'Permission reset for baseline administrators'
            ),
            self::PERM_GRANTED_FOR_BASELINE_READERS => dgettext(
                'tuleap-baseline',
                'Permission granted for baseline readers'
            ),
            self::PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS => dgettext(
                'tuleap-baseline',
                'Permission granted for baseline administrators'
            ),
            self::ALL_BASELINE_PERMS_REMOVED_FOR_UGROUP => dgettext(
                'tuleap-baseline',
                'All baseline permissions removed for user group'
            ),
            default => null,
        };
    }

    public static function fillProjectHistorySubEvents(array $params): void
    {
        array_push(
            $params['subEvents']['event_permission'],
            self::PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS,
            self::PERM_GRANTED_FOR_BASELINE_READERS,
            self::PERM_RESET_FOR_BASELINE_ADMINISTRATORS,
            self::PERM_RESET_FOR_BASELINE_READERS,
        );
    }

    public function saveHistory(RoleAssignmentsUpdate $role_assignments_update): void
    {
        $assignments_by_roles = [
            RoleBaselineReader::NAME  => [],
            RoleBaselineAdmin::NAME => [],
        ];
        foreach ($role_assignments_update->getAssignments() as $assignment) {
            $assignments_by_roles[$assignment->getRoleName()][] = $assignment;
        }

        if (empty($assignments_by_roles[RoleBaselineReader::NAME])) {
            $this->history_entry_adder->addProjectHistoryEntryForRoleAndGroups(
                $role_assignments_update->getProject(),
                self::PERM_RESET_FOR_BASELINE_READERS,
            );
        } else {
            $this->history_entry_adder->addProjectHistoryEntryForRoleAndGroups(
                $role_assignments_update->getProject(),
                self::PERM_GRANTED_FOR_BASELINE_READERS,
                ...$assignments_by_roles[RoleBaselineReader::NAME]
            );
        }

        if (empty($assignments_by_roles[RoleBaselineAdmin::NAME])) {
            $this->history_entry_adder->addProjectHistoryEntryForRoleAndGroups(
                $role_assignments_update->getProject(),
                self::PERM_RESET_FOR_BASELINE_ADMINISTRATORS,
            );
        } else {
            $this->history_entry_adder->addProjectHistoryEntryForRoleAndGroups(
                $role_assignments_update->getProject(),
                self::PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS,
                ...$assignments_by_roles[RoleBaselineAdmin::NAME]
            );
        }
    }

    public function saveUgroupDeletionHistory(
        ProjectIdentifier $project,
        BaselineUserGroup $baseline_user_group,
    ): void {
        $this->history_entry_adder->addProjectHistoryEntryForUgroupDeletion(
            $project,
            self::ALL_BASELINE_PERMS_REMOVED_FOR_UGROUP,
            $baseline_user_group,
        );
    }
}
