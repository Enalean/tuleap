<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Project;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class BuildPlanningStub implements BuildPlanning
{
    private bool $valid_root_planning;

    public function __construct(bool $valid_root_planning)
    {
        $this->valid_root_planning = $valid_root_planning;
    }

    public function getRootPlanning(UserIdentifier $user_identifier, int $project_id): \Planning
    {
        if ($this->valid_root_planning) {
            $planning = new \Planning(1, 'Root planning', $project_id, '', '');
            $planning->setPlanningTracker(
                TrackerTestBuilder::aTracker()->withId(20)
                                              ->withProject(new Project(['group_id' => 1, 'group_name' => 'My project']))
                                              ->build()
            );
            return $planning;
        }

        throw new TopPlanningNotFoundInProjectException($project_id);
    }

    public function getProjectFromPlanning(\Planning $root_planning): ProgramManagementProject
    {
        return new ProgramManagementProject(101, 'my-project', 'My project', '/project');
    }

    public static function withValidRootPlanning(): self
    {
        return new self(true);
    }

    public static function withoutRootValid(): self
    {
        return new self(false);
    }
}
