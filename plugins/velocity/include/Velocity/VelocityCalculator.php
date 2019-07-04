<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity;

use AgileDashboard_Semantic_InitialEffortFactory;
use PFUser;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElement_IComputeValues;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;

class VelocityCalculator
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;
    /**
     * @var SemanticDoneFactory
     */
    private $semantic_done_factory;
    /**
     * @var VelocityDao
     */
    private $dao;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        SemanticDoneFactory $semantic_done_factory,
        VelocityDao $dao
    ) {
        $this->artifact_factory       = $artifact_factory;
        $this->initial_effort_factory = $initial_effort_factory;
        $this->semantic_done_factory  = $semantic_done_factory;
        $this->dao                    = $dao;
    }

    public function calculate(Tracker_Artifact $artifact, PFUser $user)
    {
        $backlog_items = $this->getPlanningLinkedArtifact($artifact);

        $initial_effort = 0;
        foreach ($backlog_items as $item) {
            $artifact = $this->artifact_factory->getArtifactById($item['id']);

            $initial_effort += $this->getEffort($artifact, $user);
        }

        return $initial_effort;
    }

    private function getPlanningLinkedArtifact(Tracker_Artifact $artifact)
    {
        return $this->dao->searchPlanningLinkedArtifact($artifact->getId());
    }

    private function getEffort(Tracker_Artifact $artifact, PFUser $user)
    {
        $semantic_initial_effort = $this->initial_effort_factory->getByTracker($artifact->getTracker());
        if ($semantic_initial_effort === null) {
            return 0;
        }

        $initial_effort_field = $semantic_initial_effort->getField();
        if (! $initial_effort_field) {
            return 0;
        }

        $semantic_done = $this->semantic_done_factory->getInstanceByTracker($artifact->getTracker());
        if ($semantic_done === null) {
            return 0;
        }

        $changeset = $artifact->getLastChangeset();
        if ($changeset === null) {
            return 0;
        }

        $status_value = $changeset->getValue($semantic_done->getSemanticStatus()->getField());
        if (! $status_value) {
            return 0;
        }

        $status_values = $status_value->getValue();
        if (in_array($status_values[0], $semantic_done->getDoneValuesIds())) {
            assert($initial_effort_field instanceof Tracker_FormElement_IComputeValues);

            $initial_effort_value = $initial_effort_field->getComputedValue($user, $artifact);

            if (! $initial_effort_value || ! is_numeric($initial_effort_value)) {
                return 0;
            }

            return $initial_effort_value;
        }

        return 0;
    }
}
