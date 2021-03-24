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

use PFUser;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Program\BuildPlanning;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class FeatureRepresentationBuilder
{
    /**
     * @var BackgroundColorRetriever
     */
    private $retrieve_background_color;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ArtifactsLinkedToParentDao
     */
    private $stories_linked_to_feature_dao;
    /**
     * @var BuildPlanning
     */
    private $planning_adapter;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \Tracker_FormElementFactory $form_element_factory,
        BackgroundColorRetriever $retrieve_background_color,
        ArtifactsLinkedToParentDao $stories_linked_to_feature_dao,
        BuildPlanning $planning_adapter
    ) {
        $this->artifact_factory              = $artifact_factory;
        $this->form_element_factory          = $form_element_factory;
        $this->retrieve_background_color     = $retrieve_background_color;
        $this->stories_linked_to_feature_dao = $stories_linked_to_feature_dao;
        $this->planning_adapter              = $planning_adapter;
    }

    public function buildFeatureRepresentation(
        PFUser $user,
        int $artifact_id,
        int $title_field_id,
        string $artifact_title
    ): ?FeatureRepresentation {
        $full_artifact = $this->artifact_factory->getArtifactById($artifact_id);

        if (! $full_artifact || ! $full_artifact->userCanView($user)) {
            return null;
        }

        $title = $this->form_element_factory->getFieldById($title_field_id);
        if (! $title || ! $title->userCanRead($user)) {
            return null;
        }

        return new FeatureRepresentation(
            $artifact_id,
            $artifact_title,
            $full_artifact->getXRef(),
            MinimalTrackerRepresentation::build($full_artifact->getTracker()),
            $this->retrieve_background_color->retrieveBackgroundColor($full_artifact, $user),
            $this->hasAPlannedStory($user, $artifact_id),
            $this->hasStoryLinked($user, $artifact_id)
        );
    }

    private function hasAPlannedStory(PFUser $user, int $artifact_id): bool
    {
        $planned_user_stories = $this->stories_linked_to_feature_dao->getPlannedUserStory($artifact_id);
        foreach ($planned_user_stories as $user_story) {
            $planning = $this->planning_adapter->buildRootPlanning($user, $user_story['project_id']);

            $is_linked_to_a_sprint_in_mirrored_milestones = $this->stories_linked_to_feature_dao->isLinkedToASprintInMirroredMilestones(
                $user_story['user_story_id'],
                $planning->getPlanningTracker()->getTrackerId(),
                $user_story['project_id']
            );
            if ($is_linked_to_a_sprint_in_mirrored_milestones) {
                return true;
            }
        }

        return false;
    }

    private function hasStoryLinked(PFUser $user, int $artifact_id): bool
    {
        $linked_children = $this->stories_linked_to_feature_dao->getChildrenOfFeatureInTeamProjects($artifact_id);
        foreach ($linked_children as $linked_child) {
            $child = $this->artifact_factory->getArtifactByIdUserCanView($user, $linked_child['children_id']);
            if ($child) {
                return true;
            }
        }

        return false;
    }
}
