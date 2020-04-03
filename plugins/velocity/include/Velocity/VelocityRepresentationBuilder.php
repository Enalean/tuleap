<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Velocity\Semantic\SemanticVelocityFactory;

class VelocityRepresentationBuilder
{
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var SemanticVelocityFactory
     */
    private $semantic_velocity_factory;

    /**
     * @var SemanticDoneFactory
     */
    private $semantic_done_factory;

    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

    public function __construct(
        SemanticVelocityFactory $semantic_velocity_factory,
        SemanticDoneFactory $semantic_done_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        Planning_MilestoneFactory $milestone_factory
    ) {
        $this->milestone_factory          = $milestone_factory;
        $this->semantic_velocity_factory  = $semantic_velocity_factory;
        $this->semantic_done_factory      = $semantic_done_factory;
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
    }

    public function buildCollectionOfRepresentations(Planning_Milestone $milestone, PFUser $user): VelocityCollection
    {
        $representations = new VelocityCollection();

        $sub_milestones = $this->milestone_factory->getSubMilestones($user, $milestone);

        foreach ($sub_milestones as $sub_milestone) {
            $artifact           = $sub_milestone->getArtifact();
            $velocity           = $this->semantic_velocity_factory->getInstanceByTracker($artifact->getTracker());
            $done_semantic      = $this->semantic_done_factory->getInstanceByTracker($artifact->getTracker());
            $timeframe_semantic = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());

            if (! $timeframe_semantic->isDefined()) {
                $representations->addInvalidTracker($artifact->getTracker());
            }

            if ($velocity->getVelocityField() && $done_semantic->isDone($artifact->getLastChangeset()) && $timeframe_semantic->isDefined()) {
                $computed_velocity = $artifact->getLastChangeset()->getValue($velocity->getVelocityField());
                $this->milestone_factory->updateMilestoneContextualInfo($user, $sub_milestone);

                $start_date = $sub_milestone->getStartDate();

                if ($start_date) {
                    $representation = new VelocityRepresentation(
                        $artifact->getId(),
                        $artifact->getTitle(),
                        $start_date,
                        $sub_milestone->getDuration(),
                        ($computed_velocity) ? $computed_velocity->getNumeric() : 0
                    );
                    $representations->addVelocityRepresentation($representation);
                } else {
                    $representation = new InvalidArtifactRepresentation();
                    $representation->build($artifact);
                    $representations->addInvalidArtifact($representation);
                }
            }
        }

        return $representations;
    }
}
