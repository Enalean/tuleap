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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use PFUser;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Project;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\Layout\SidebarPromotedItemPresenter;
use Tuleap\Option\Option;
use function Psl\Type\non_empty_string;
use function Psl\Type\shape;
use function Psl\Type\string;

final class AgileDashboardPromotedMilestonesRetriever
{
    public function __construct(
        private readonly Planning_MilestoneFactory $milestone_factory,
        private readonly Project $project,
        private readonly CheckMilestonesInSidebar $milestones_in_sidebar,
        private readonly BuildPromotedMilestoneList $promoted_milestone_list_builder,
    ) {
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getSidebarPromotedMilestones(PFUser $user, ?string $active_promoted_item_id): array
    {
        if (! $this->milestones_in_sidebar->shouldSidebarDisplayLastMilestones((int) $this->project->getID())) {
            return [];
        }

        try {
            $virtual_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $this->project);
        } catch (Planning_NoPlanningsException) {
            return [];
        }

        $milestones = $this->promoted_milestone_list_builder->buildPromotedMilestoneList($user, $virtual_milestone);

        $items = [];
        foreach ($milestones->getMilestoneList() as $milestone_struct) {
            $milestone      = $milestone_struct->getMilestone();
            $sub_milestones = $milestone_struct->getSubMilestoneList();

            $this->getDataForMilestone($milestone)->apply(function ($data) use (&$items, $sub_milestones, $active_promoted_item_id, $milestone) {
                $is_one_sub_milestone_active = false;
                $sub_milestones_items        = [];
                foreach ($sub_milestones as $sub_milestone) {
                    $this->getDataForMilestone($sub_milestone)->apply(function ($data) use (&$sub_milestones_items, &$is_one_sub_milestone_active, $active_promoted_item_id, $sub_milestone) {
                        $is_active = $active_promoted_item_id === $sub_milestone->getPromotedMilestoneId();
                        if ($is_active) {
                            $is_one_sub_milestone_active = true;
                        }
                        $sub_milestones_items[] = new SidebarPromotedItemPresenter(
                            $data['uri'],
                            $data['title'],
                            $data['description'],
                            $is_active,
                            null,
                            []
                        );
                    });
                }

                $items[] = new SidebarPromotedItemPresenter(
                    $data['uri'],
                    $data['title'],
                    $data['description'],
                    $is_one_sub_milestone_active || $active_promoted_item_id === $milestone->getPromotedMilestoneId(),
                    null,
                    $sub_milestones_items
                );
            });
        }

        return $items;
    }

    /**
     * @return Option<array{
     *     uri: non-empty-string,
     *     title: non-empty-string,
     *     description: string
     * }>
     */
    private function getDataForMilestone(Planning_ArtifactMilestone $milestone): Option
    {
        $artifact = $milestone->getArtifact();

        $title = $artifact->getTitle();
        if ($title === null || $title === '') {
            return Option::nothing(shape([
                'uri'         => non_empty_string(),
                'title'       => non_empty_string(),
                'description' => string(),
            ]));
        }

        $description = $artifact->getDescription();

        $uri = '/plugins/agiledashboard/?' . http_build_query([
            'group_id'    => $this->project->getID(),
            'planning_id' => $milestone->getPlanningId(),
            'action'      => 'show',
            'aid'         => $artifact->getId(),
            'pane'        => DetailsPaneInfo::IDENTIFIER,
        ]);

        return Option::fromValue([
            'uri'         => $uri,
            'title'       => $title,
            'description' => $description,
        ]);
    }
}
