<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\BreadCrumbDropdown;

use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Planning_MilestonePaneFactory;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkWithIcon;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class MilestoneCrumbBuilder
{
    /** @var string */
    private $plugin_path;

    /** @var Planning_MilestonePaneFactory */
    private $pane_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    public function __construct(
        $plugin_path,
        Planning_MilestonePaneFactory $pane_factory,
        Planning_MilestoneFactory $milestone_factory
    ) {
        $this->plugin_path       = $plugin_path;
        $this->pane_factory      = $pane_factory;
        $this->milestone_factory = $milestone_factory;
    }

    /**
     *
     * @return BreadCrumb
     */
    public function build(PFUser $user, Planning_Milestone $milestone)
    {
        $this->milestone_factory->addMilestoneAncestors($user, $milestone);
        $milestone_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                $milestone->getArtifactTitle(),
                $this->getOverviewUrl($milestone)
            )
        );
        $milestone_breadcrumb->setSubItems($this->getSubItems($user, $milestone));

        return $milestone_breadcrumb;
    }

    private function getOverviewUrl(Planning_Milestone $milestone)
    {
        return $this->plugin_path . '/?' .
            http_build_query(
                [
                    'planning_id' => $milestone->getPlanningId(),
                    'pane'        => DetailsPaneInfo::IDENTIFIER,
                    'action'      => 'show',
                    'group_id'    => $milestone->getGroupId(),
                    'aid'         => $milestone->getArtifactId()
                ]
            );
    }

    private function getArtifactUrl(Planning_Milestone $milestone)
    {
        return '/plugins/tracker/?' .
            http_build_query(
                ['aid' => $milestone->getArtifactId()]
            );
    }

    /**
     *
     * @return BreadCrumbSubItems
     */
    private function getSubItems(PFUser $user, Planning_Milestone $milestone)
    {
        $sub_items = new BreadCrumbSubItems();
        $this->addDefaultSection($milestone, $sub_items);
        $this->addSiblingsSection($user, $milestone, $sub_items);

        return $sub_items;
    }

    private function addDefaultSection(Planning_Milestone $milestone, BreadCrumbSubItems $sub_items)
    {
        $links = [];
        $panes = $this->pane_factory->getListOfPaneInfo($milestone);
        foreach ($panes as $pane) {
            $links[] = new BreadCrumbLinkWithIcon(
                $pane->getTitle(),
                $pane->getUri(),
                $pane->getIconName()
            );
        }
        $links[] = new BreadCrumbLinkWithIcon(
            $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'artifact'),
            $this->getArtifactUrl($milestone),
            'fa-list-ol'
        );
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection($links)
            )
        );
    }

    private function addSiblingsSection(PFUser $user, Planning_Milestone $milestone, BreadCrumbSubItems $sub_items)
    {
        $links = $this->getFirstTenOpenSiblings($user, $milestone);

        if (empty($links)) {
            return;
        }

        $sub_items->addSection(
            new SubItemsSection(
                sprintf(
                    dngettext('tuleap-agiledashboard', 'Other %s', 'Other %s', count($links)),
                    $milestone->getArtifact()->getTracker()->getName()
                ),
                new BreadCrumbLinkCollection($links)
            )
        );
    }

    /**
     *
     * @return array
     */
    private function getFirstTenOpenSiblings(PFUser $user, Planning_Milestone $milestone)
    {
        $links     = [];
        $criterion = new StatusOpen();
        $limit     = 10;
        $offset    = 0;
        do {
            $paginated_milestones = $this->milestone_factory->getPaginatedSiblingMilestonesWithStatusCriterion(
                $user,
                $milestone,
                $criterion,
                $limit,
                $offset
            );
            foreach ($paginated_milestones->getMilestones() as $sibling) {
                $links[] = new BreadCrumbLink(
                    $sibling->getArtifactTitle(),
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
