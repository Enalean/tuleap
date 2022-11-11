<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;

/**
 * @psalm-immutable
 */
final class ConfigurationErrorsGathererBuilder
{
    public static function buildWithError(ProjectReference $project_reference, ProjectReference $team_reference): ConfigurationErrorsGatherer
    {
        return new ConfigurationErrorsGatherer(
            BuildProgramStub::stubValidProgram(),
            ProgramIncrementCreatorCheckerBuilder::buildInvalid(),
            IterationCreatorCheckerBuilder::build(),
            SearchTeamsOfProgramStub::withTeamIds($team_reference->getId()),
            RetrieveProjectReferenceStub::withProjects($project_reference, $team_reference)
        );
    }

    public static function build(ProjectReference $project_reference, ProjectReference $team_reference): ConfigurationErrorsGatherer
    {
        return new ConfigurationErrorsGatherer(
            BuildProgramStub::stubValidProgram(),
            ProgramIncrementCreatorCheckerBuilder::build(),
            IterationCreatorCheckerBuilder::build(),
            SearchTeamsOfProgramStub::withTeamIds($team_reference->getId()),
            RetrieveProjectReferenceStub::withProjects($project_reference, $team_reference)
        );
    }
}
