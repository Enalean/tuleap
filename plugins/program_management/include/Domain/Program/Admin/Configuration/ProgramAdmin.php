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
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeamsCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\AllProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsSynchronizationPending;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\VerifyTeamSynchronizationHasError;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsAProgramOrUsedInPlanChecker;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsProjectUsedInPlan;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\SearchProjectsUserIsAdmin;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\SearchTrackersOfProgram;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\VerifyTrackerSemantics;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

/**
 * @psalm-immutable
 */
final class ProgramAdmin
{
    /**
     * @param PotentialTeam[] $potential_teams
     * @param ProgramAdminTeam[] $aggregated_teams
     * @param ProgramSelectOptionConfiguration[] $potential_program_increments
     * @param ProgramSelectOptionConfiguration[] $potential_plannable_trackers
     * @param ProgramSelectOptionConfiguration[] $ugroups_can_prioritize
     * @param ProgramSelectOptionConfiguration[] $potential_iterations
     */
    private function __construct(
        public ProgramForAdministrationIdentifier $program,
        public string $program_shortname,
        public array $potential_teams,
        public array $aggregated_teams,
        public array $potential_program_increments,
        public array $potential_plannable_trackers,
        public array $ugroups_can_prioritize,
        public ?string $program_increment_label,
        public ?string $program_increment_sub_label,
        public array $potential_iterations,
        public ?string $iteration_label,
        public ?string $iteration_sub_label,
        public ?TrackerError $program_increment_error,
        public ?TrackerError $iteration_error,
        public ?TrackerError $plannable_error,
        public bool $has_presenter_errors,
        public bool $has_plannable_error,
        public bool $has_program_increment_error,
        public bool $has_iteration_error,
        public bool $is_project_used_in_plan,
        public string $project_team_access_errors,
    ) {
    }

    /**
     * @throws ProgramAccessException
     */
    public static function build(
        SearchProjectsUserIsAdmin $search_project_user_is_admin,
        SearchTeamsOfProgram $teams_searcher,
        VerifyIsTeam $verify_is_team,
        BuildProgram $build_program,
        RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        PotentialPlannableTrackersConfigurationBuilder $plannable_tracker_presenters_builder,
        ProjectUGroupCanPrioritizeItemsBuilder $ugroups_can_prioritize_builder,
        RetrieveProgramIncrementLabels $program_increment_labels_retriever,
        SearchTrackersOfProgram $trackers_searcher,
        RetrieveIterationLabels $iteration_labels_retriever,
        AllProgramSearcher $all_program_searcher,
        ConfigurationErrorsGatherer $errors_gatherer,
        SearchOpenProgramIncrements $search_open_program_increments,
        SearchMirrorTimeboxesFromProgram $timebox_searcher,
        VerifyIsSynchronizationPending $verify_is_synchronization_pending,
        SearchVisibleTeamsOfProgram $team_searcher,
        VerifyTeamSynchronizationHasError $verify_team_synchronization_has_error,
        RetrievePlannableTrackers $plannable_trackers_retriever,
        VerifyTrackerSemantics $verify_tracker_semantics,
        ProgramForAdministrationIdentifier $program_for_administration_identifier,
        UserReference $user_identifier,
        ProjectReference $project_reference,
        TeamProjectsCollection $aggregated_teams,
        ProjectIsAProgramOrUsedInPlanChecker $build_program_for_administration,
        VerifyIsProjectUsedInPlan $verify_is_project_used_in_plan,
    ): self {
        $increment_error              = null;
        $iteration_error              = null;
        $plannable_error              = null;
        $program_increment_tracker    = null;
        $iteration_tracker_identifier = null;
        $iteration_tracker            = null;


        try {
            $program = ProgramIdentifier::fromIdForAdministration(
                $build_program_for_administration,
                $program_for_administration_identifier,
                $user_identifier,
            );

            $program_increment_error_collector = new ConfigurationErrorsCollector($verify_is_team, true);
            $iteration_error_collector         = new ConfigurationErrorsCollector($verify_is_team, true);
            $plannable_error_collector         = new ConfigurationErrorsCollector($verify_is_team, true);

            $program_increment_tracker = $program_increment_tracker_retriever->retrieveVisibleProgramIncrementTracker(
                $program,
                $user_identifier
            );

            $iteration_tracker_identifier = IterationTrackerIdentifier::fromProgram(
                $iteration_tracker_retriever,
                $program,
                $user_identifier
            );

            $increment_error = TrackerError::buildProgramIncrementError(
                $errors_gatherer,
                $program_increment_tracker,
                $program,
                $user_identifier,
                $program_increment_error_collector
            );

            $iteration_tracker = $iteration_tracker_retriever->retrieveVisibleIterationTracker($program, $user_identifier);
            $iteration_error   = TrackerError::buildIterationError(
                $errors_gatherer,
                $iteration_tracker,
                $user_identifier,
                $iteration_error_collector
            );
            $plannable_error   = TrackerError::buildPlannableError(
                $plannable_trackers_retriever,
                $verify_tracker_semantics,
                $program,
                $plannable_error_collector
            );
        } catch (
            ProjectIsNotAProgramException
            | ProgramHasNoProgramIncrementTrackerException
            | ProgramTrackerNotFoundException
        ) {
        }

        $program_increment_labels = ProgramIncrementLabels::fromProgramIncrementTracker(
            $program_increment_labels_retriever,
            $program_increment_tracker
        );

        $iteration_labels = IterationLabels::fromIterationTracker(
            $iteration_labels_retriever,
            $iteration_tracker_identifier
        );

        $all_potential_trackers = PotentialTrackerCollection::fromProgram(
            $trackers_searcher,
            $program_for_administration_identifier
        );

        $has_program_increment_error   = $increment_error && $increment_error->has_presenter_errors;
        $has_iteration_increment_error = $iteration_error && $iteration_error->has_presenter_errors;
        $has_plannable_error           = $plannable_error && $plannable_error->has_presenter_errors;
        $has_errors                    = $has_program_increment_error || $has_iteration_increment_error || $has_plannable_error;

        $project_team_access_errors = '';
        try {
            $program_admin_team = ProgramAdminTeam::build(
                $search_open_program_increments,
                $timebox_searcher,
                $program_for_administration_identifier,
                $user_identifier,
                $aggregated_teams,
                $verify_is_synchronization_pending,
                $team_searcher,
                $verify_team_synchronization_has_error,
                $build_program,
                $plannable_error,
                $increment_error,
                $iteration_error
            );
        } catch (TeamIsNotVisibleException $exception) {
            $program_admin_team         = [];
            $project_team_access_errors = $exception->team_project_name;
        }

        return new self(
            $program_for_administration_identifier,
            $project_reference->getProjectShortName(),
            PotentialTeamsCollection::buildPotentialTeams(
                $teams_searcher,
                $all_program_searcher,
                $search_project_user_is_admin,
                $program_for_administration_identifier,
                $user_identifier
            )->getPotentialTeams(),
            $program_admin_team,
            PotentialTimeboxTrackerConfigurationCollection::fromTimeboxTracker(
                $all_potential_trackers,
                $program_increment_tracker
            )->presenters,
            $plannable_tracker_presenters_builder->buildPotentialPlannableTracker(
                $program_for_administration_identifier,
                $all_potential_trackers
            ),
            $ugroups_can_prioritize_builder->buildProjectUgroupCanPrioritizeItemsPresenters($program_for_administration_identifier),
            $program_increment_labels->label,
            $program_increment_labels->sub_label,
            PotentialTimeboxTrackerConfigurationCollection::fromTimeboxTracker(
                $all_potential_trackers,
                $iteration_tracker
            )->presenters,
            $iteration_labels->label,
            $iteration_labels->sub_label,
            $increment_error,
            $iteration_error,
            $plannable_error,
            $has_errors,
            $has_plannable_error,
            $has_program_increment_error,
            $has_iteration_increment_error,
            $verify_is_project_used_in_plan->isProjectUsedInPlan($program_for_administration_identifier) || ! empty($program_admin_team),
            $project_team_access_errors,
        );
    }
}
