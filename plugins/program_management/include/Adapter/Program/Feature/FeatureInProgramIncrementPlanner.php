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
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\FeatureToLinkBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoriesLinkedToMilestoneBuilder;
use Tuleap\ProgramManagement\Adapter\Team\MirroredMilestones\MirroredMilestoneRetriever;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Program\Backlog\Feature\PlanFeatureInProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\PlannedProgramIncrement;

class FeatureInProgramIncrementPlanner implements PlanFeatureInProgramIncrement
{
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var FeatureToLinkBuilder
     */
    private $feature_to_plan_builder;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var MirroredMilestoneRetriever
     */
    private $mirrored_milestone_retriever;
    /**
     * @var ContentDao
     */
    private $content_dao;
    /**
     * @var UserStoriesLinkedToMilestoneBuilder
     */
    private $features_linked_to_milestone_builder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DBTransactionExecutor $db_transaction_executor,
        FeatureToLinkBuilder $feature_to_plan_builder,
        \Tracker_ArtifactFactory $tracker_artifact_factory,
        MirroredMilestoneRetriever $mirrored_milestone_retriever,
        ContentDao $content_dao,
        UserStoriesLinkedToMilestoneBuilder $features_linked_to_milestone_builder,
        LoggerInterface $logger
    ) {
        $this->db_transaction_executor              = $db_transaction_executor;
        $this->tracker_artifact_factory             = $tracker_artifact_factory;
        $this->mirrored_milestone_retriever         = $mirrored_milestone_retriever;
        $this->content_dao                          = $content_dao;
        $this->features_linked_to_milestone_builder = $features_linked_to_milestone_builder;
        $this->feature_to_plan_builder              = $feature_to_plan_builder;
        $this->logger                               = $logger;
    }

    /**
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Plan\PlannableTrackerCannotBeEmptyException
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException
     */
    public function plan(ProgramIncrementChanged $feature_to_plan): void
    {
        $this->logger->debug("Check if we need to plan/unplan items in mirrored releases.");
        $program_increment_id         = $feature_to_plan->program_increment_id;
        $user                         = $feature_to_plan->user;
        $program_increment_tracker_id = $feature_to_plan->tracker_id;

        $potential_feature_to_link = $this->content_dao->searchContent(
            new PlannedProgramIncrement($program_increment_id)
        );
        $feature_plan_change       = $this->feature_to_plan_builder->buildFeatureChange(
            $potential_feature_to_link,
            $program_increment_tracker_id
        );

        $this->db_transaction_executor->execute(
            function () use ($feature_plan_change, $user, $program_increment_id) {
                $milestones = $this->mirrored_milestone_retriever->retrieveMilestonesLinkedTo($program_increment_id);
                foreach ($milestones as $mirrored_milestone) {
                    $this->logger->error(sprintf("Found mirrored milestone %d", $mirrored_milestone->getId()));
                    $milestone = $this->tracker_artifact_factory->getArtifactById($mirrored_milestone->getId());
                    if (! $milestone) {
                        $this->logger->error(sprintf("Mirrored milestone %d not found", $mirrored_milestone->getId()));
                        continue;
                    }

                    $field_artifact_link = $milestone->getAnArtifactLinkField($user);
                    if (! $field_artifact_link) {
                        $this->logger->info(sprintf("Mirrored milestone %d does not have an artifact link field", $mirrored_milestone->getId()));
                        continue;
                    }

                    $fields_data = new FieldData(
                        $feature_plan_change->user_stories,
                        $this->features_linked_to_milestone_builder->build($mirrored_milestone),
                        $field_artifact_link->getId()
                    );

                    try {
                        $this->logger->debug(
                            sprintf(
                                "Change in PI #%d trying to add a changeset to the mirrored milestone #%d",
                                $program_increment_id,
                                $milestone->getId()
                            )
                        );
                        $milestone->createNewChangeset(
                            $fields_data->getFieldDataForChangesetCreationFormat(),
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
