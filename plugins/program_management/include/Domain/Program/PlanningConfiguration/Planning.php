<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration;

use Tuleap\ProgramManagement\Adapter\Workspace\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class Planning
{
    private function __construct(
        private TrackerReference $planning_tracker,
        private int $id,
        private string $name,
        private array $backlog_tracker_ids,
        private ProgramManagementProject $project_data
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlanningTracker(): TrackerReference
    {
        return $this->planning_tracker;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlannableTrackerIds(): array
    {
        return $this->backlog_tracker_ids;
    }

    public function getProjectData(): ProgramManagementProject
    {
        return $this->project_data;
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanningHasNoProgramIncrementException
     */
    public static function buildPlanning(BuildPlanning $build_planning, UserIdentifier $user_identifier, int $project_id): self
    {
        $root_planning = $build_planning->getRootPlanning(
            $user_identifier,
            $project_id
        );

        return new self(
            TrackerReferenceProxy::fromTracker($root_planning->getPlanningTracker()),
            $root_planning->getId(),
            $root_planning->getName(),
            $root_planning->getBacklogTrackersIds(),
            $build_planning->getProjectFromPlanning($root_planning)
        );
    }
}
