<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

final class ConfigurationErrorsGatherer
{
    private BuildProgram $build_program;
    private ProgramIncrementCreatorChecker $program_increment_creator_checker;
    private IterationCreatorChecker $iteration_creator_checker;
    private SearchTeamsOfProgram $teams_searcher;
    private RetrieveProjectReference $project_builder;

    public function __construct(
        BuildProgram $build_program,
        ProgramIncrementCreatorChecker $program_increment_creator_checker,
        IterationCreatorChecker $iteration_creator_checker,
        SearchTeamsOfProgram $teams_searcher,
        RetrieveProjectReference $project_builder,
    ) {
        $this->build_program                     = $build_program;
        $this->program_increment_creator_checker = $program_increment_creator_checker;
        $this->iteration_creator_checker         = $iteration_creator_checker;
        $this->teams_searcher                    = $teams_searcher;
        $this->project_builder                   = $project_builder;
    }

    public function gatherConfigurationErrors(
        TrackerReference $tracker,
        UserReference $user_identifier,
        ConfigurationErrorsCollector $errors_collector,
    ): void {
        try {
            $program = ProgramIdentifier::fromId(
                $this->build_program,
                $tracker->getProjectId(),
                $user_identifier,
                null
            );
        } catch (ProgramAccessException | ProjectIsNotAProgramException $e) {
            // Do not disable artifact submission. Keep it enabled
            return;
        }

        $team_projects_collection = TeamProjectsCollection::fromProgramIdentifier(
            $this->teams_searcher,
            $this->project_builder,
            $program
        );

        $this->program_increment_creator_checker->canCreateAProgramIncrement(
            $tracker,
            $program,
            $team_projects_collection,
            $errors_collector,
            $user_identifier
        );

        if ($errors_collector->hasError() && ! $errors_collector->shouldCollectAllIssues()) {
            return;
        }

        $this->iteration_creator_checker->canCreateAnIteration(
            $tracker,
            $program,
            $team_projects_collection,
            $errors_collector,
            $user_identifier
        );
    }
}
