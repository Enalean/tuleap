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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AllProgramSearcherStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIsAProgramOrUsedInPlanCheckerStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildUGroupRepresentationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUGroupsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProjectsUserIsAdminStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTrackersOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProjectUsedInPlanStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsSynchronizationPendingStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTeamSynchronizationHasErrorStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTrackerSemanticsStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramAdminTest extends TestCase
{
    private BuildProgramStub $build_program;
    private ProjectReferenceStub $project_reference;
    private ProjectReferenceStub $team_reference;
    private SearchTeamsOfProgramStub $teams_searcher;

    protected function setUp(): void
    {
        $this->project_reference = ProjectReferenceStub::withId(1233);
        $this->team_reference    = ProjectReferenceStub::withId(987);
        $this->build_program     = BuildProgramStub::stubValidProgram();
        $this->teams_searcher    = SearchTeamsOfProgramStub::withTeamIds($this->team_reference->getId());
    }

    private function getProgramAdmin(ConfigurationErrorsGatherer $errors_gatherer): ProgramAdmin
    {
        $program_increment_tracker    = TrackerReferenceStub::withId(1);
        $iteration_tracker            = TrackerReferenceStub::withId(2);
        $plannable_tracker            = TrackerReferenceStub::withId(3);
        $plannable_trackers_retriever = RetrievePlannableTrackersStub::build($plannable_tracker);

        $verify_tracker_semantics = VerifyTrackerSemanticsStub::withAllSemantics();

        $search_project_user_is_admin = SearchProjectsUserIsAdminStub::buildWithProjects($this->project_reference);

        $program_increment_tracker_retriever   = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker);
        $iteration_tracker_retriever           = RetrieveVisibleIterationTrackerStub::withValidTracker($iteration_tracker);
        $plannable_tracker_presenters_builder  = new PotentialPlannableTrackersConfigurationBuilder($plannable_trackers_retriever);
        $ugroups_can_prioritize_builder        = new ProjectUGroupCanPrioritizeItemsBuilder(
            RetrieveUGroupsStub::buildWithUGroups(),
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds($this->team_reference->getId()),
            BuildUGroupRepresentationStub::build()
        );
        $program_increment_labels_retriever    = RetrieveProgramIncrementLabelsStub::buildLabels('PI', 'pi');
        $trackers_searcher                     = SearchTrackersOfProgramStub::withTrackers($program_increment_tracker, $iteration_tracker);
        $iteration_labels_retriever            = RetrieveIterationLabelsStub::buildLabels('Iteration', 'iteration');
        $all_program_searcher                  = AllProgramSearcherStub::buildPrograms($this->project_reference->getId());
        $search_open_program_increments        = SearchOpenProgramIncrementsStub::withoutProgramIncrements();
        $timebox_searcher                      = SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror();
        $verify_is_synchronization_pending     = VerifyIsSynchronizationPendingStub::withoutOnGoingSynchronization();
        $team_searcher                         = SearchVisibleTeamsOfProgramStub::withTeamIds($this->team_reference->getId());
        $verify_team_synchronization_has_error = VerifyTeamSynchronizationHasErrorStub::buildWithoutError();
        $program_for_administration_identifier = ProgramForAdministrationIdentifierBuilder::buildWithId($this->project_reference->getId());

        $aggregated_teams = TeamProjectsCollectionBuilder::withEmptyTeams();

        return ProgramAdmin::build(
            $search_project_user_is_admin,
            $this->teams_searcher,
            VerifyIsTeamStub::withValidTeam(),
            $this->build_program,
            $program_increment_tracker_retriever,
            $iteration_tracker_retriever,
            $plannable_tracker_presenters_builder,
            $ugroups_can_prioritize_builder,
            $program_increment_labels_retriever,
            $trackers_searcher,
            $iteration_labels_retriever,
            $all_program_searcher,
            $errors_gatherer,
            $search_open_program_increments,
            $timebox_searcher,
            $verify_is_synchronization_pending,
            $team_searcher,
            $verify_team_synchronization_has_error,
            $plannable_trackers_retriever,
            $verify_tracker_semantics,
            $program_for_administration_identifier,
            UserReferenceStub::withDefaults(),
            $this->project_reference,
            $aggregated_teams,
            ProjectIsAProgramOrUsedInPlanCheckerStub::stubValidProgram(),
            VerifyIsProjectUsedInPlanStub::withProjectUsedInPlan()
        );
    }

    public function testItBuildAProgramAdminWithError(): void
    {
        $errors_gatherer = new ConfigurationErrorsGatherer(
            $this->build_program,
            ProgramIncrementCreatorCheckerBuilder::buildInvalid(),
            IterationCreatorCheckerBuilder::build(),
            $this->teams_searcher,
            RetrieveProjectReferenceStub::withProjects($this->project_reference, $this->team_reference)
        );
        $program_admin   = $this->getProgramAdmin($errors_gatherer);

        self::assertTrue($program_admin->has_presenter_errors);
    }

    public function testItBuildAProgramAdmin(): void
    {
        $errors_gatherer = new ConfigurationErrorsGatherer(
            $this->build_program,
            ProgramIncrementCreatorCheckerBuilder::build(),
            IterationCreatorCheckerBuilder::build(),
            $this->teams_searcher,
            RetrieveProjectReferenceStub::withProjects($this->project_reference, $this->team_reference)
        );

        $program_admin = $this->getProgramAdmin($errors_gatherer);

        self::assertFalse($program_admin->has_presenter_errors);
    }

    public function testItSaysThatItIsUsedInPlanIfThereIsAtLeastOneTeamDefined(): void
    {
        $errors_gatherer = new ConfigurationErrorsGatherer(
            $this->build_program,
            ProgramIncrementCreatorCheckerBuilder::build(),
            IterationCreatorCheckerBuilder::build(),
            $this->teams_searcher,
            RetrieveProjectReferenceStub::withProjects($this->project_reference, $this->team_reference)
        );

        $program_admin = $this->getProgramAdminNotUsedInPlanWithTeam($errors_gatherer);

        self::assertTrue($program_admin->is_project_used_in_plan);
    }

    public function testItSaysThatItIsNotUsedInPlanIfThereIsNoTeamDefinedAndProjectNotUsedInPlan(): void
    {
        $errors_gatherer = new ConfigurationErrorsGatherer(
            $this->build_program,
            ProgramIncrementCreatorCheckerBuilder::build(),
            IterationCreatorCheckerBuilder::build(),
            $this->teams_searcher,
            RetrieveProjectReferenceStub::withProjects($this->project_reference, $this->team_reference)
        );

        $program_admin = $this->getProgramAdminNotUsedInPlanWithoutTeam($errors_gatherer);

        self::assertFalse($program_admin->is_project_used_in_plan);
    }

    private function getProgramAdminNotUsedInPlanWithTeam(ConfigurationErrorsGatherer $errors_gatherer): ProgramAdmin
    {
        $program_increment_tracker    = TrackerReferenceStub::withId(1);
        $iteration_tracker            = TrackerReferenceStub::withId(2);
        $plannable_tracker            = TrackerReferenceStub::withId(3);
        $plannable_trackers_retriever = RetrievePlannableTrackersStub::build($plannable_tracker);

        $verify_tracker_semantics = VerifyTrackerSemanticsStub::withAllSemantics();

        $search_project_user_is_admin = SearchProjectsUserIsAdminStub::buildWithProjects($this->project_reference);

        $program_increment_tracker_retriever   = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker);
        $iteration_tracker_retriever           = RetrieveVisibleIterationTrackerStub::withValidTracker($iteration_tracker);
        $plannable_tracker_presenters_builder  = new PotentialPlannableTrackersConfigurationBuilder($plannable_trackers_retriever);
        $ugroups_can_prioritize_builder        = new ProjectUGroupCanPrioritizeItemsBuilder(
            RetrieveUGroupsStub::buildWithUGroups(),
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds($this->team_reference->getId()),
            BuildUGroupRepresentationStub::build()
        );
        $program_increment_labels_retriever    = RetrieveProgramIncrementLabelsStub::buildLabels('PI', 'pi');
        $trackers_searcher                     = SearchTrackersOfProgramStub::withTrackers($program_increment_tracker, $iteration_tracker);
        $iteration_labels_retriever            = RetrieveIterationLabelsStub::buildLabels('Iteration', 'iteration');
        $all_program_searcher                  = AllProgramSearcherStub::buildPrograms($this->project_reference->getId());
        $search_open_program_increments        = SearchOpenProgramIncrementsStub::withoutProgramIncrements();
        $timebox_searcher                      = SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror();
        $verify_is_synchronization_pending     = VerifyIsSynchronizationPendingStub::withoutOnGoingSynchronization();
        $team_searcher                         = SearchVisibleTeamsOfProgramStub::withTeamIds($this->team_reference->getId());
        $verify_team_synchronization_has_error = VerifyTeamSynchronizationHasErrorStub::buildWithoutError();
        $program_for_administration_identifier = ProgramForAdministrationIdentifierBuilder::buildWithId($this->project_reference->getId());

        $aggregated_teams = TeamProjectsCollectionBuilder::withProjects(
            $this->team_reference,
        );

        return ProgramAdmin::build(
            $search_project_user_is_admin,
            $this->teams_searcher,
            VerifyIsTeamStub::withValidTeam(),
            $this->build_program,
            $program_increment_tracker_retriever,
            $iteration_tracker_retriever,
            $plannable_tracker_presenters_builder,
            $ugroups_can_prioritize_builder,
            $program_increment_labels_retriever,
            $trackers_searcher,
            $iteration_labels_retriever,
            $all_program_searcher,
            $errors_gatherer,
            $search_open_program_increments,
            $timebox_searcher,
            $verify_is_synchronization_pending,
            $team_searcher,
            $verify_team_synchronization_has_error,
            $plannable_trackers_retriever,
            $verify_tracker_semantics,
            $program_for_administration_identifier,
            UserReferenceStub::withDefaults(),
            $this->project_reference,
            $aggregated_teams,
            ProjectIsAProgramOrUsedInPlanCheckerStub::stubValidProgram(),
            VerifyIsProjectUsedInPlanStub::withProjectNotUsedInPlan()
        );
    }

    private function getProgramAdminNotUsedInPlanWithoutTeam(ConfigurationErrorsGatherer $errors_gatherer): ProgramAdmin
    {
        $program_increment_tracker    = TrackerReferenceStub::withId(1);
        $iteration_tracker            = TrackerReferenceStub::withId(2);
        $plannable_tracker            = TrackerReferenceStub::withId(3);
        $plannable_trackers_retriever = RetrievePlannableTrackersStub::build($plannable_tracker);

        $verify_tracker_semantics = VerifyTrackerSemanticsStub::withAllSemantics();

        $search_project_user_is_admin = SearchProjectsUserIsAdminStub::buildWithProjects($this->project_reference);

        $program_increment_tracker_retriever   = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker);
        $iteration_tracker_retriever           = RetrieveVisibleIterationTrackerStub::withValidTracker($iteration_tracker);
        $plannable_tracker_presenters_builder  = new PotentialPlannableTrackersConfigurationBuilder($plannable_trackers_retriever);
        $ugroups_can_prioritize_builder        = new ProjectUGroupCanPrioritizeItemsBuilder(
            RetrieveUGroupsStub::buildWithUGroups(),
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds($this->team_reference->getId()),
            BuildUGroupRepresentationStub::build()
        );
        $program_increment_labels_retriever    = RetrieveProgramIncrementLabelsStub::buildLabels('PI', 'pi');
        $trackers_searcher                     = SearchTrackersOfProgramStub::withTrackers($program_increment_tracker, $iteration_tracker);
        $iteration_labels_retriever            = RetrieveIterationLabelsStub::buildLabels('Iteration', 'iteration');
        $all_program_searcher                  = AllProgramSearcherStub::buildPrograms($this->project_reference->getId());
        $search_open_program_increments        = SearchOpenProgramIncrementsStub::withoutProgramIncrements();
        $timebox_searcher                      = SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror();
        $verify_is_synchronization_pending     = VerifyIsSynchronizationPendingStub::withoutOnGoingSynchronization();
        $team_searcher                         = SearchVisibleTeamsOfProgramStub::withTeamIds($this->team_reference->getId());
        $verify_team_synchronization_has_error = VerifyTeamSynchronizationHasErrorStub::buildWithoutError();
        $program_for_administration_identifier = ProgramForAdministrationIdentifierBuilder::buildWithId($this->project_reference->getId());

        $aggregated_teams = TeamProjectsCollectionBuilder::withEmptyTeams();

        return ProgramAdmin::build(
            $search_project_user_is_admin,
            $this->teams_searcher,
            VerifyIsTeamStub::withValidTeam(),
            $this->build_program,
            $program_increment_tracker_retriever,
            $iteration_tracker_retriever,
            $plannable_tracker_presenters_builder,
            $ugroups_can_prioritize_builder,
            $program_increment_labels_retriever,
            $trackers_searcher,
            $iteration_labels_retriever,
            $all_program_searcher,
            $errors_gatherer,
            $search_open_program_increments,
            $timebox_searcher,
            $verify_is_synchronization_pending,
            $team_searcher,
            $verify_team_synchronization_has_error,
            $plannable_trackers_retriever,
            $verify_tracker_semantics,
            $program_for_administration_identifier,
            UserReferenceStub::withDefaults(),
            $this->project_reference,
            $aggregated_teams,
            ProjectIsAProgramOrUsedInPlanCheckerStub::stubValidProgram(),
            VerifyIsProjectUsedInPlanStub::withProjectNotUsedInPlan()
        );
    }
}
