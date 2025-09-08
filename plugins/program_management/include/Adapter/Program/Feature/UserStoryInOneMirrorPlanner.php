<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Psr\Log\LoggerInterface;
use Tracker_NoChangeException;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\PlanUserStoryInOneMirror;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

final class UserStoryInOneMirrorPlanner implements PlanUserStoryInOneMirror
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
        private LoggerInterface $logger,
        private CreateNewChangeset $create_new_changeset,
        private RetrieveUser $retrieve_user,
        private \Tracker_FormElementFactory $form_element_factory,
    ) {
    }

    #[\Override]
    public function planInOneMirror(
        ProgramIncrementIdentifier $program_increment,
        MirroredProgramIncrementIdentifier $mirrored_program_increment,
        FeaturePlanChange $feature_plan_change,
        UserIdentifier $user_identifier,
    ): void {
        $this->logger->info(sprintf('Found mirrored PI %d', $mirrored_program_increment->getId()));
        $mirror_artifact = $this->artifact_retriever->getNonNullArtifact($mirrored_program_increment);

        $user                = $this->retrieve_user->getUserWithId($user_identifier);
        $field_artifact_link = $this->form_element_factory->getAnArtifactLinkField($user, $mirror_artifact->getTracker());
        if (! $field_artifact_link) {
            $this->logger->info(
                sprintf(
                    'Mirrored PI %d does not have an artifact link field',
                    $mirrored_program_increment->getId()
                )
            );
            return;
        }

        $fields_data = new FieldData(
            $feature_plan_change->user_stories,
            $feature_plan_change->user_stories_to_remove,
            $field_artifact_link->getId(),
        );

        try {
            $team_id = $mirror_artifact->getTracker()->getGroupId();
            $this->logger->debug(
                sprintf(
                    'Change in PI #%d trying to add a changeset to the mirrored PI #%d in team project #%d',
                    $program_increment->getId(),
                    $mirror_artifact->getId(),
                    $team_id
                )
            );

            $this->create_new_changeset->create(
                NewChangeset::fromFieldsDataArrayWithEmptyComment(
                    $mirror_artifact,
                    $fields_data->getFieldDataForChangesetCreationFormat(
                        (int) $team_id
                    ),
                    $user,
                    (new \DateTimeImmutable())->getTimestamp()
                ),
                PostCreationContext::withNoConfig(true)
            );
        } catch (Tracker_NoChangeException $e) {
            //Don't stop transaction if linked artifact is not concerned by the change
        }
    }
}
