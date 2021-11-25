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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Psr\Log\LoggerInterface;
use Tracker_NoChangeException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\RetrieveUnlinkedUserStoriesOfMirroredProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;

final class UserStoriesInMirroredProgramIncrementsPlanner implements PlanUserStoriesInMirroredProgramIncrements
{
    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private SearchArtifactsLinks $artifacts_links_search,
        private RetrieveFullArtifact $artifact_retriever,
        private SearchMirroredTimeboxes $mirrored_timeboxes_searcher,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private ContentStore $content_dao,
        private LoggerInterface $logger,
        private RetrieveUser $retrieve_user,
        private RetrieveUnlinkedUserStoriesOfMirroredProgramIncrement $linked_to_parent_dao
    ) {
    }

    public function plan(ProgramIncrementChanged $program_increment_changed): void
    {
        $this->logger->debug("Check if we need to plan/unplan items in mirrored releases.");
        $program_increment         = $program_increment_changed->program_increment;
        $user_identifier           = $program_increment_changed->user;
        $potential_feature_to_link = $this->content_dao->searchContent($program_increment->getId());
        $feature_plan_change       = FeaturePlanChange::fromRaw(
            $this->artifacts_links_search,
            $potential_feature_to_link,
            $program_increment_changed->tracker->getId()
        );

        $user = $this->retrieve_user->getUserWithId($user_identifier);
        $this->db_transaction_executor->execute(
            function () use ($feature_plan_change, $user, $user_identifier, $program_increment) {
                $mirrored_program_increments = MirroredProgramIncrementIdentifier::buildCollectionOnlyWhenUserCanSee(
                    $this->mirrored_timeboxes_searcher,
                    $this->visibility_verifier,
                    $program_increment,
                    $user_identifier
                );
                foreach ($mirrored_program_increments as $mirrored_program_increment) {
                    $this->planInOneMirror(
                        $program_increment,
                        $mirrored_program_increment,
                        $feature_plan_change,
                        $user
                    );
                }
            }
        );
    }

    private function planInOneMirror(
        ProgramIncrementIdentifier $program_increment,
        MirroredProgramIncrementIdentifier $mirrored_program_increment,
        FeaturePlanChange $feature_plan_change,
        \PFUser $user
    ): void {
        $this->logger->info(sprintf("Found mirrored PI %d", $mirrored_program_increment->getId()));
        $mirror_artifact = $this->artifact_retriever->getNonNullArtifact($mirrored_program_increment);

        $field_artifact_link = $mirror_artifact->getAnArtifactLinkField($user);
        if (! $field_artifact_link) {
            $this->logger->info(
                sprintf(
                    "Mirrored PI %d does not have an artifact link field",
                    $mirrored_program_increment->getId()
                )
            );
            return;
        }

        $fields_data = new FieldData(
            $feature_plan_change->user_stories,
            $this->linked_to_parent_dao->getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint(
                $mirrored_program_increment->getId()
            ),
            $field_artifact_link->getId()
        );

        try {
            $this->logger->debug(
                sprintf(
                    "Change in PI #%d trying to add a changeset to the mirrored PI #%d",
                    $program_increment->getId(),
                    $mirror_artifact->getId()
                )
            );
            $mirror_artifact->createNewChangeset(
                $fields_data->getFieldDataForChangesetCreationFormat(
                    (int) $mirror_artifact->getTracker()->getGroupId()
                ),
                "",
                $user
            );
        } catch (Tracker_NoChangeException $e) {
            //Don't stop transaction if linked artifact is not concerned by the change
        }
    }
}
