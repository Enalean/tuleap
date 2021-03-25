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

use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\Links\RetrieveFeatureBacklogItems;
use Tuleap\ProgramManagement\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\REST\v1\FeatureBacklogItemsRepresentation;
use Tuleap\Project\REST\ProjectReference;

class FeatureBacklogItemsRepresentationBuilder implements RetrieveFeatureBacklogItems
{
    /**
     * @var ArtifactsLinkedToParentDao
     */
    private $dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var PlanStore
     */
    private $plan_store;
    /**
     * @var BackgroundColorRetriever
     */
    private $retrieve_background_color;

    public function __construct(
        ArtifactsLinkedToParentDao $dao,
        \Tracker_ArtifactFactory $artifact_factory,
        PlanStore $plan_store,
        BackgroundColorRetriever $retrieve_background_color
    ) {
        $this->dao                       = $dao;
        $this->artifact_factory          = $artifact_factory;
        $this->plan_store                = $plan_store;
        $this->retrieve_background_color = $retrieve_background_color;
    }

    /**
     * @return FeatureBacklogItemsRepresentation[]
     * @throws FeatureNotAccessException
     * @throws FeatureIsNotPlannableException
     */
    public function buildFeatureBacklogItems(int $feature_id, \PFUser $user): array
    {
        $feature = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        if (! $feature) {
            throw new FeatureNotAccessException();
        }
        $feature_tracker_is_plannable = $this->plan_store->isPlannable($feature->getTrackerId());

        if (! $feature_tracker_is_plannable) {
            throw new FeatureIsNotPlannableException($feature->getTrackerId());
        }

        $linked_children  = [];
        $planned_children = $this->dao->getChildrenOfFeatureInTeamProjects($feature_id);
        foreach ($planned_children as $planned_child) {
            $child = $this->artifact_factory->getArtifactByIdUserCanView($user, $planned_child['children_id']);
            if ($child) {
                $linked_children[] = new FeatureBacklogItemsRepresentation(
                    $child->getId(),
                    $child->getUri(),
                    $child->getXRef(),
                    $child->getTitle(),
                    $child->isOpen(),
                    new ProjectReference($child->getTracker()->getProject()),
                    $child->getTracker()->getColor()->getName(),
                    $this->retrieve_background_color->retrieveBackgroundColor($child, $user)->getBackgroundColorName(),
                );
            }
        }

        return $linked_children;
    }
}
