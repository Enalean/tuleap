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

use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\ProgramManagement\Adapter\Program\TeamPlanningProxy;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TeamPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class BuildPlanningStub implements BuildPlanning
{
    public function __construct(private bool $valid_root_planning)
    {
    }

    public function getRootPlanning(UserIdentifier $user_identifier, int $project_id): TeamPlanning
    {
        if ($this->valid_root_planning) {
            $project           = ProjectTestBuilder::aProject()->withId($project_id)->build();
            $milestone_tracker = TrackerTestBuilder::aTracker()
                ->withId(20)
                ->withProject($project)
                ->build();
            $planning          = PlanningBuilder::aPlanning($project_id)
                ->withMilestoneTracker($milestone_tracker)
                ->build();
            return TeamPlanningProxy::fromPlanning($planning);
        }

        throw new TopPlanningNotFoundInProjectException($project_id);
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
