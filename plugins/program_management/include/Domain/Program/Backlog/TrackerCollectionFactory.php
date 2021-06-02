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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Project;

interface TrackerCollectionFactory
{
    /**
     * @throws Plan\PlanCheckException
     * @throws PlanningNotFoundException
     * @throws PlanTrackerException
     * @throws ProgramTrackerNotFoundException
     * @throws PlanningHasNoProgramIncrementException
     * @throws TrackerRetrievalException
     */
    public function buildFromProgramProjectAndItsTeam(
        Project $program_project,
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): SourceTrackerCollection;
}
