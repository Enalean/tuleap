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
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\RetrieveUnlinkedUserStoriesOfMirroredProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;

final class UserStoriesInMirroredProgramIncrementsPlanner implements PlanUserStoriesInMirroredProgramIncrements
{
    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private ArtifactsLinkedToParentDao $artifacts_linked_to_parent_dao,
        private \Tracker_ArtifactFactory $tracker_artifact_factory,
        private SearchMirroredTimeboxes $mirrored_timeboxes_searcher,
        private ContentStore $content_dao,
        private LoggerInterface $logger,
        private RetrieveUser $retrieve_user,
        private RetrieveUnlinkedUserStoriesOfMirroredProgramIncrement $linked_to_parent_dao
    ) {
    }

    public function plan(ProgramIncrementChanged $program_increment_changed): void
    {
        $this->logger->debug("Check if we need to plan/unplan items in mirrored releases.");
        $program_increment_id         = $program_increment_changed->program_increment_id;
        $user_identifier              = $program_increment_changed->user;
        $program_increment_tracker_id = $program_increment_changed->tracker_id;

        $potential_feature_to_link = $this->content_dao->searchContent(
            $program_increment_id
        );
        $feature_plan_change       = FeaturePlanChange::fromRaw(
            $this->artifacts_linked_to_parent_dao,
            $potential_feature_to_link,
            $program_increment_tracker_id
        );

        $user = $this->retrieve_user->getUserWithId($user_identifier);
        $this->db_transaction_executor->execute(
            function () use ($feature_plan_change, $user, $program_increment_id) {
                $mirrored_program_increments = $this->mirrored_timeboxes_searcher->searchMirroredTimeboxes(
                    $program_increment_id
                );
                foreach ($mirrored_program_increments as $mirrored_program_increment) {
                    $this->logger->info(sprintf("Found mirrored PI %d", $mirrored_program_increment->getId()));
                    $mirror_artifact = $this->tracker_artifact_factory->getArtifactById(
                        $mirrored_program_increment->getId()
                    );
                    if (! $mirror_artifact) {
                        $this->logger->error(
                            sprintf("Mirrored PI %d is not an artifact", $mirrored_program_increment->getId())
                        );
                        continue;
                    }

                    $field_artifact_link = $mirror_artifact->getAnArtifactLinkField($user);
                    if (! $field_artifact_link) {
                        $this->logger->info(
                            sprintf(
                                "Mirrored PI %d does not have an artifact link field",
                                $mirrored_program_increment->getId()
                            )
                        );
                        continue;
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
                                $program_increment_id,
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
        );
    }
}
