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

use Planning_Milestone;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkWithIcon;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class MilestoneCrumbBuilder
{
    /** @var string */
    private $plugin_path;

    /** @var \Planning_MilestonePaneFactory */
    private $pane_factory;

    public function __construct($plugin_path, \Planning_MilestonePaneFactory $pane_factory)
    {
        $this->plugin_path  = $plugin_path;
        $this->pane_factory = $pane_factory;
    }

    /**
     * @param Planning_Milestone $milestone
     *
     * @return BreadCrumb
     */
    public function build(Planning_Milestone $milestone)
    {
        $milestone_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                $milestone->getArtifactTitle(),
                $this->getPlanningUrl($milestone)
            )
        );
        $milestone_breadcrumb->setSubItems($this->getSubItems($milestone));

        return $milestone_breadcrumb;
    }

    private function getPlanningUrl(Planning_Milestone $milestone)
    {
        return $this->plugin_path . '/?' .
            http_build_query(
                [
                    'planning_id' => $milestone->getPlanningId(),
                    'pane'        => 'planning-v2',
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
     * @param Planning_Milestone $milestone
     *
     * @return BreadCrumbSubItems
     */
    private function getSubItems(Planning_Milestone $milestone)
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
            'fa-list-ol icon-list-ol'
        );

        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection($links)
            )
        );

        return $sub_items;
    }
}
