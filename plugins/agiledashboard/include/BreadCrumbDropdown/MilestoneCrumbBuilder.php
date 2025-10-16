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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\BreadCrumbDropdown;

use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PlanningMilestonePaneFactory;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

final readonly class MilestoneCrumbBuilder
{
    public function __construct(
        private string $plugin_path,
        private PlanningMilestonePaneFactory $pane_factory,
        private Planning_MilestoneFactory $milestone_factory,
    ) {
    }

    public function build(PFUser $user, Planning_Milestone $milestone): BreadCrumb
    {
        $this->milestone_factory->addMilestoneAncestors($user, $milestone);
        $milestone_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                (string) $milestone->getArtifactTitle(),
                $this->getOverviewUrl($milestone)
            )
        );
        $milestone_breadcrumb->setSubItems($this->getSubItems($user, $milestone));

        return $milestone_breadcrumb;
    }

    private function getOverviewUrl(Planning_Milestone $milestone): string
    {
        return $this->plugin_path . '/?' .
               http_build_query([
                   'planning_id' => $milestone->getPlanningId(),
                   'pane'        => DetailsPaneInfo::IDENTIFIER,
                   'action'      => 'show',
                   'group_id'    => $milestone->getGroupId(),
                   'aid'         => $milestone->getArtifactId(),
               ]);
    }

    private function getArtifactUrl(Planning_Milestone $milestone): string
    {
        return '/plugins/tracker/?' . http_build_query(['aid' => $milestone->getArtifactId()]);
    }

    private function getSubItems(PFUser $user, Planning_Milestone $milestone): BreadCrumbSubItems
    {
        $sub_items = new BreadCrumbSubItems();
        $this->addDefaultSection($milestone, $sub_items, $user);
        $this->addSiblingsSection($user, $milestone, $sub_items);

        return $sub_items;
    }

    private function addDefaultSection(Planning_Milestone $milestone, BreadCrumbSubItems $sub_items, PFUser $user): void
    {
        $links = [];
        $panes = $this->pane_factory->getListOfPaneInfo($milestone, $user);
        foreach ($panes as $pane) {
            $links[] = new BreadCrumbLink(
                $pane->getTitle(),
                $pane->getUri(),
            );
        }
        $links[] = new BreadCrumbLink(
            dgettext('tuleap-tracker', 'Artifact'),
            $this->getArtifactUrl($milestone),
        );
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection($links)
            )
        );
    }

    private function addSiblingsSection(PFUser $user, Planning_Milestone $milestone, BreadCrumbSubItems $sub_items): void
    {
        $links = $this->getFirstTenOpenSiblings($user, $milestone);

        if (empty($links)) {
            return;
        }

        $sub_items->addSection(
            new SubItemsSection(
                sprintf(
                    dngettext('tuleap-agiledashboard', 'Other %s', 'Other %s', count($links)),
                    $milestone->getArtifact()->getTracker()->getItemName()
                ),
                new BreadCrumbLinkCollection($links)
            )
        );
    }

    /**
     * @return list<BreadCrumbLink>
     */
    private function getFirstTenOpenSiblings(PFUser $user, Planning_Milestone $milestone): array
    {
        $links   = [];
        $limit   = 10;
        $offset  = 0;
        $request = new SiblingMilestoneRequest($user, $milestone, $limit, $offset, new StatusOpen());
        do {
            $paginated_milestones = $this->milestone_factory->getPaginatedSiblingMilestones($request);
            foreach ($paginated_milestones->getMilestones() as $sibling) {
                $links[] = new BreadCrumbLink(
                    (string) $sibling->getArtifactTitle(),
                    $this->getOverviewUrl($sibling)
                );
                if (count($links) === 10) {
                    return $links;
                }
            }
            $offset += $limit;
        } while ($offset < $paginated_milestones->getTotalSize());

        return $links;
    }
}
