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

use Tracker_NoChangeException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoriesLinkedToMilestoneBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\FeatureToLinkBuilder;
use Tuleap\ProgramManagement\Adapter\Team\MirroredMilestones\MirroredMilestoneRetriever;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\PlanFeatureInProgramIncrement;
use Tuleap\ProgramManagement\Team\MirroredMilestone\MirroredMilestone;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

class FeaturePlanner implements PlanFeatureInProgramIncrement
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

    public function __construct(
        DBTransactionExecutor $db_transaction_executor,
        FeatureToLinkBuilder $feature_to_plan_builder,
        \Tracker_ArtifactFactory $tracker_artifact_factory,
        MirroredMilestoneRetriever $mirrored_milestone_retriever,
        ContentDao $content_dao,
        UserStoriesLinkedToMilestoneBuilder $features_linked_to_milestone_builder
    ) {
        $this->db_transaction_executor              = $db_transaction_executor;
        $this->tracker_artifact_factory             = $tracker_artifact_factory;
        $this->mirrored_milestone_retriever         = $mirrored_milestone_retriever;
        $this->content_dao                          = $content_dao;
        $this->features_linked_to_milestone_builder = $features_linked_to_milestone_builder;
        $this->feature_to_plan_builder              = $feature_to_plan_builder;
    }

    /**
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Plan\PlannableTrackerCannotBeEmptyException
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException
     */
    public function plan(ArtifactUpdated $event): void
    {
        $artifact                     = $event->getArtifact();
        $program_increment_id         = $artifact->getId();
        $user                         = $event->getUser();
        $program_increment_tracker_id = $event->getArtifact()->getTrackerId();

        $potential_feature_to_link = $this->content_dao->searchContent(
            new PlannedProgramIncrement($artifact->getId())
        );
        $feature_list_to_plan      = $this->feature_to_plan_builder->buildFeatureChange($potential_feature_to_link, $program_increment_tracker_id);

        $this->db_transaction_executor->execute(
            function () use ($feature_list_to_plan, $user, $program_increment_id) {
                $milestones = $this->mirrored_milestone_retriever->retrieveMilestonesLinkedTo($program_increment_id);
                foreach ($milestones as $mirrored_milestone) {
                    $milestone = $this->tracker_artifact_factory->getArtifactById($mirrored_milestone->getId());
                    if (! $milestone) {
                        continue;
                    }

                    $field_artifact_link = $milestone->getAnArtifactLinkField($user);
                    if (! $field_artifact_link) {
                        continue;
                    }

                    $fields_data                                                  = [];
                    $fields_data[$field_artifact_link->getId()]['new_values']     =
                        implode(",", $feature_list_to_plan->features_id);
                    $fields_data[$field_artifact_link->getId()]['removed_values'] =
                        $this->getUserStoriesThatAreLinkedToMilestoneAndNoLongerInArtifactLinkList(
                            $mirrored_milestone,
                            $feature_list_to_plan
                        );
                    try {
                        $milestone->createNewChangeset($fields_data, "", $user);
                    } catch (Tracker_NoChangeException $e) {
                        //Don't stop transaction if linked artifact is not concerned by the change
                    }
                }
            }
        );
    }

    /**
     * @return array
     */
    private function getUserStoriesThatAreLinkedToMilestoneAndNoLongerInArtifactLinkList(
        MirroredMilestone $mirrored_milestone,
        FeaturePlanChange $feature_list_to_plan
    ): array {
        $potential_user_stories_to_remove = $this->features_linked_to_milestone_builder->build(
            $mirrored_milestone
        );

        $user_stories_to_remove = [];
        foreach ($potential_user_stories_to_remove as $key => $value) {
            if (! in_array($key, $feature_list_to_plan->features_id, true)) {
                $user_stories_to_remove[$key] = $key;
            }
        }

        return $user_stories_to_remove;
    }
}
