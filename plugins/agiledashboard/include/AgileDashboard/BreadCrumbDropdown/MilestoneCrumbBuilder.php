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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbItem;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItemCollection;

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
     * @return BreadCrumbItem
     */
    public function build(Planning_Milestone $milestone)
    {
        $sub_items = new BreadCrumbSubItemCollection();
        $panes     = $this->pane_factory->getListOfPaneInfo($milestone);

        foreach ($panes as $pane) {
            $pane_breadcrumb = new BreadCrumbItem(
                $pane->getTitle(),
                $pane->getUri()
            );
            $pane_breadcrumb->setIconName($pane->getIconName());

            $sub_items->addBreadCrumb($pane_breadcrumb);
        }
        $artifact_breadcrumb = new BreadCrumbItem(
            $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'artifact'),
            $this->getArtifactUrl($milestone)
        );
        $artifact_breadcrumb->setIconName('fa-list-ol icon-list-ol');

        $sub_items->addBreadCrumb($artifact_breadcrumb);

        $milestone_breadcrumb = new BreadCrumbItem(
            $milestone->getArtifactTitle(),
            $this->getPlanningUrl($milestone)
        );
        $milestone_breadcrumb->setSubItems($sub_items);

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
}
