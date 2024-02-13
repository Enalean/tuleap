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

namespace Tuleap\Baseline\Stub;

use Tuleap\Baseline\Domain\AddRoleAssignmentsHistoryEntry;
use Tuleap\Baseline\Domain\BaselineUserGroup;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\RoleAssignment;

final class AddRoleAssignmentsHistoryEntryStub implements AddRoleAssignmentsHistoryEntry
{
    public function __construct(private array $added_history_entries)
    {
    }

    public static function build(): self
    {
        return new self([]);
    }

    public function addProjectHistoryEntryForRoleAndGroups(ProjectIdentifier $project, string $history_key, RoleAssignment ...$assignments): void
    {
        $this->added_history_entries[] = [$project, $history_key, $assignments];
    }

    public function getAddedHistoryEntries(): array
    {
        return $this->added_history_entries;
    }

    public function addProjectHistoryEntryForUgroupDeletion(ProjectIdentifier $project, string $history_key, BaselineUserGroup $baseline_user_group): void
    {
        $this->added_history_entries[] = [$project, $history_key, $baseline_user_group];
    }
}
