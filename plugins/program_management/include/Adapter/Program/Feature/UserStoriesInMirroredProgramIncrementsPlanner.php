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
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxRetriever;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;

class UserStoriesInMirroredProgramIncrementsPlanner implements PlanUserStoriesInMirroredProgramIncrements
{
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var MirroredTimeboxRetriever
     */
    private $mirrored_timebox_retriever;
    /**
     * @var ContentDao
     */
    private $content_dao;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ArtifactsLinkedToParentDao
     */
    private $artifacts_linked_to_parent_dao;

    public function __construct(
        DBTransactionExecutor $db_transaction_executor,
        ArtifactsLinkedToParentDao $artifacts_linked_to_parent_dao,
        \Tracker_ArtifactFactory $tracker_artifact_factory,
        MirroredTimeboxRetriever $mirrored_timebox_retriever,
        ContentDao $content_dao,
        LoggerInterface $logger
    ) {
        $this->db_transaction_executor        = $db_transaction_executor;
        $this->tracker_artifact_factory       = $tracker_artifact_factory;
        $this->mirrored_timebox_retriever     = $mirrored_timebox_retriever;
        $this->content_dao                    = $content_dao;
        $this->logger                         = $logger;
        $this->artifacts_linked_to_parent_dao = $artifacts_linked_to_parent_dao;
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\PlannableTrackerCannotBeEmptyException
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    public function plan(ProgramIncrementChanged $program_increment_changed): void
    {
        $this->logger->debug("Check if we need to plan/unplan items in mirrored releases.");
        $program_increment_id         = $program_increment_changed->program_increment_id;
        $user                         = $program_increment_changed->user;
        $program_increment_tracker_id = $program_increment_changed->tracker_id;

        $potential_feature_to_link = $this->content_dao->searchContent(
            $program_increment_id
        );
        $feature_plan_change       = FeaturePlanChange::fromRaw(
            $this->artifacts_linked_to_parent_dao,
            $potential_feature_to_link,
            $program_increment_tracker_id
        );

        $this->db_transaction_executor->execute(
            function () use ($feature_plan_change, $user, $program_increment_id) {
                $program_increments = $this->mirrored_timebox_retriever->retrieveMilestonesLinkedTo($program_increment_id);
                foreach ($program_increments as $mirrored_program_increment) {
                    $this->logger->info(sprintf("Found mirrored PI %d", $mirrored_program_increment->getId()));
                    $mirror_artifact = $this->tracker_artifact_factory->getArtifactById($mirrored_program_increment->getId());
                    if (! $mirror_artifact) {
                        $this->logger->error(sprintf("Mirrored PI %d is not an artifact", $mirrored_program_increment->getId()));
                        continue;
                    }

                    $field_artifact_link = $mirror_artifact->getAnArtifactLinkField($user);
                    if (! $field_artifact_link) {
                        $this->logger->info(sprintf("Mirrored PI %d does not have an artifact link field", $mirrored_program_increment->getId()));
                        continue;
                    }

                    $fields_data = new FieldData(
                        $feature_plan_change->user_stories,
                        $this->artifacts_linked_to_parent_dao->getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint($mirrored_program_increment->getId()),
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
                            $fields_data->getFieldDataForChangesetCreationFormat((int) $mirror_artifact->getTracker()->getGroupId()),
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
