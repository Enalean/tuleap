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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProjectFromTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTrackerFromField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanSubmit;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class TimeboxCreatorChecker
{
    public function __construct(
        private SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder $field_collection_builder,
        private VerifySemanticsAreConfigured $semantics_verifier,
        private VerifyRequiredFieldsLimitedToSynchronizedFields $required_fields_verifier,
        private VerifySynchronizedFieldsAreNotUsedInWorkflow $workflow_verifier,
        private RetrieveTrackerFromField $retrieve_tracker_from_field,
        private RetrieveProjectFromTracker $retrieve_project_from_tracker,
        private VerifyUserCanSubmit $user_can_submit_in_tracker_verifier,
    ) {
    }

    public function canTimeboxBeCreated(
        TrackerReference $tracker,
        SourceTrackerCollection $program_and_milestone_trackers,
        TrackerCollection $team_trackers,
        UserIdentifier $user_identifier,
        ConfigurationErrorsCollector $configuration_errors,
    ): bool {
        $can_be_created = true;
        if (! $this->semantics_verifier->areTrackerSemanticsWellConfigured($tracker, $program_and_milestone_trackers, $configuration_errors)) {
            $can_be_created = false;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return $can_be_created;
            }
        }

        if (! $team_trackers->canUserSubmitAnArtifactInAllTrackers($user_identifier, $configuration_errors, $this->user_can_submit_in_tracker_verifier)) {
            $can_be_created = false;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return $can_be_created;
            }
        }

        try {
            $synchronized_fields_data_collection = $this->field_collection_builder->buildFromSourceTrackers($program_and_milestone_trackers, $configuration_errors);

            if (! $synchronized_fields_data_collection->canUserSubmitAndUpdateAllFields($user_identifier, $configuration_errors)) {
                $can_be_created = false;
                if (! $configuration_errors->shouldCollectAllIssues()) {
                    return $can_be_created;
                }
            }

            if (
                ! $this->required_fields_verifier->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
                    $team_trackers,
                    $synchronized_fields_data_collection,
                    $configuration_errors,
                    $this->retrieve_tracker_from_field,
                    $this->retrieve_project_from_tracker
                )
            ) {
                $can_be_created = false;
                if (! $configuration_errors->shouldCollectAllIssues()) {
                    return $can_be_created;
                }
            }

            if (
                ! $this->workflow_verifier->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                    $team_trackers,
                    $synchronized_fields_data_collection,
                    $configuration_errors
                )
            ) {
                $can_be_created = false;
                if (! $configuration_errors->shouldCollectAllIssues()) {
                    return $can_be_created;
                }
            }
        } catch (FieldSynchronizationException $exception) {
            $can_be_created = false;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return $can_be_created;
            }
        }

        return $can_be_created;
    }
}
