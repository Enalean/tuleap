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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\Planning;

final class UserStoryLinkedToFeatureChecker implements VerifyLinkedUserStoryIsNotPlanned
{
    /**
     * @var ArtifactsLinkedToParentDao
     */
    private $stories_linked_to_feature_dao;
    /**
     * @var BuildPlanning
     */
    private $planning_adapter;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        ArtifactsLinkedToParentDao $stories_linked_to_feature_dao,
        BuildPlanning $planning_adapter,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->stories_linked_to_feature_dao = $stories_linked_to_feature_dao;
        $this->planning_adapter              = $planning_adapter;
        $this->artifact_factory              = $artifact_factory;
    }

    public function isLinkedToAtLeastOnePlannedUserStory(PFUser $user, FeatureIdentifier $feature): bool
    {
        $planned_user_stories = $this->stories_linked_to_feature_dao->getPlannedUserStory($feature->id);
        foreach ($planned_user_stories as $user_story) {
            $planning = Planning::buildPlanning($this->planning_adapter, $user, $user_story['project_id']);

            $is_linked_to_a_sprint_in_mirrored_program_increments = $this->stories_linked_to_feature_dao->isLinkedToASprintInMirroredProgramIncrement(
                $user_story['user_story_id'],
                $planning->getPlanningTracker()->getTrackerId(),
                $user_story['project_id']
            );
            if ($is_linked_to_a_sprint_in_mirrored_program_increments) {
                return true;
            }
        }

        return false;
    }

    public function hasStoryLinked(PFUser $user, FeatureIdentifier $feature): bool
    {
        $linked_children = $this->stories_linked_to_feature_dao->getChildrenOfFeatureInTeamProjects($feature->id);
        foreach ($linked_children as $linked_child) {
            $child = $this->artifact_factory->getArtifactByIdUserCanView($user, $linked_child['children_id']);
            if ($child) {
                return true;
            }
        }

        return false;
    }
}
