<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Stub\Milestone;

use Tuleap\AgileDashboard\Milestone\Sidebar\RetrieveMilestonesWithSubMilestones;

final class RetrieveMilestonesWithSubMilestonesStub implements RetrieveMilestonesWithSubMilestones
{
    /**
     * @param array<array-key, array{
     *      parent_id: string,
     *      parent_tracker: string,
     *      parent_changeset: string,
     *      parent_submitted_by: string,
     *      parent_submitted_on: string,
     *      parent_use_artifact_permissions: string,
     *      parent_per_tracker_artifact_id: string,
     *      submilestone_id: ?string,
     *      submilestone_tracker: ?string,
     *      submilestone_changeset: ?string,
     *      submilestone_submitted_by: ?string,
     *      submilestone_submitted_on: ?string,
     *      submilestone_use_artifact_permissions: ?string,
     *      submilestone_per_tracker_artifact_id: ?string,
     *  }> $milestones
     */
    private function __construct(
        private readonly array $milestones,
    ) {
    }

    public static function withoutMilestones(): self
    {
        return new self([]);
    }

    /**
     * @param array<array-key, array{
     *     parent_id: string,
     *     parent_tracker: string,
     *     parent_changeset: string,
     *     parent_submitted_by: string,
     *     parent_submitted_on: string,
     *     parent_use_artifact_permissions: string,
     *     parent_per_tracker_artifact_id: string,
     *     submilestone_id: ?string,
     *     submilestone_tracker: ?string,
     *     submilestone_changeset: ?string,
     *     submilestone_submitted_by: ?string,
     *     submilestone_submitted_on: ?string,
     *     submilestone_use_artifact_permissions: ?string,
     *     submilestone_per_tracker_artifact_id: ?string,
     * }> $milestones
     */
    public static function withMilestones(array $milestones): self
    {
        return new self($milestones);
    }

    public function retrieveMilestonesWithSubMilestones(int $project_id, int $parent_tracker_id): array
    {
        return $this->milestones;
    }
}
