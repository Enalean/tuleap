<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use AgileDashboard_Milestone_PaginatedMilestonesRepresentations;
use PFUser;
use Planning_MilestoneFactory;
use Project;

class CurrentMilestoneRepresentationBuilder implements MilestoneRepresentationBuilderInterface
{

    /** @var AgileDashboard_Milestone_MilestoneRepresentationBuilder */
    private $representation_builder;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    public function __construct(
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $representationBuilder,
        Planning_MilestoneFactory $milestone_factory
    ) {
        $this->representation_builder = $representationBuilder;
        $this->milestone_factory      = $milestone_factory;
    }

    public function getPaginatedTopMilestonesRepresentations(
        Project $project,
        PFUser $user,
        string $representation_type,
        int $limit,
        int $offset,
        string $order
    ): AgileDashboard_Milestone_PaginatedMilestonesRepresentations {
        $sub_milestones = $this->milestone_factory
            ->getCurrentPaginatedTopMilestones($user, $project, $limit, $offset, $order);

        $milestones_representations = [];
        foreach ($sub_milestones->getMilestones() as $milestone) {
            $milestones_representations[] = $this->representation_builder->getMilestoneRepresentation($milestone, $user, $representation_type);
        }

        return new AgileDashboard_Milestone_PaginatedMilestonesRepresentations(
            $milestones_representations,
            $sub_milestones->getTotalSize()
        );
    }
}
