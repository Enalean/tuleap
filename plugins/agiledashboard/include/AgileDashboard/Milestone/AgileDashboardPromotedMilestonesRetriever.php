<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use PFUser;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Milestone\Request\FilteringQuery;
use Tuleap\AgileDashboard\Milestone\Request\PeriodQuery;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Sidebar\CheckMilestonesInSidebar;
use Tuleap\Layout\SidebarPromotedItemPresenter;

final class AgileDashboardPromotedMilestonesRetriever
{
    public function __construct(
        private readonly \Planning_MilestoneFactory $factory,
        private readonly \Project $project,
        private readonly CheckMilestonesInSidebar $milestones_in_sidebar,
    ) {
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getSidebarPromotedMilestones(PFUser $user): array
    {
        if (! $this->milestones_in_sidebar->shouldSidebarDisplayLastMilestones((int) $this->project->getID())) {
            return [];
        }

        $paginated_milestones = $this->factory->getPaginatedTopMilestones(new TopMilestoneRequest(
            $user,
            $this->project,
            5,
            0,
            'desc',
            FilteringQuery::fromPeriodQuery(PeriodQuery::createCurrent())
        ));

        $items = [];
        foreach ($paginated_milestones->getMilestones() as $milestone) {
            $artifact = $milestone->getArtifact();

            $title_field = \Tracker_Semantic_Title::load($artifact->getTracker())->getField();
            if ($title_field === null) {
                continue;
            }
            $title = $artifact->getValue($title_field);

            $description_field = \Tracker_Semantic_Description::load($artifact->getTracker())->getField();
            if ($description_field === null) {
                continue;
            }
            $description = $artifact->getValue($description_field);

            $uri = '/plugins/agiledashboard/?' . http_build_query([
                'group_id' => $this->project->getID(),
                'planning_id' => $milestone->getPlanningId(),
                'action' => 'show',
                'aid' => $artifact->getId(),
                'pane' => PlanningV2PaneInfo::IDENTIFIER,
            ]);


            $items[] = new SidebarPromotedItemPresenter(
                $uri,
                $title ? $title->getValue() : '',
                $description ? $description->getValue() : '',
                false,
                null,
            );
        }

        return $items;
    }
}
