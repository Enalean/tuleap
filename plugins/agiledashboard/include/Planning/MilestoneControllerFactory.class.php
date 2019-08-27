<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

/**
 * I build MilestoneController
 */
class Planning_MilestoneControllerFactory
{
    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Planning_MilestonePaneFactory */
    private $pane_factory;

    /** @var Planning_VirtualTopMilestonePaneFactory */
    private $top_milestone_pane_factory;

    /** @var AgileDashboardCrumbBuilder */
    private $service_crumb_builder;

    /** @var VirtualTopMilestoneCrumbBuilder */
    private $top_milestone_crumb_builder;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var AllBreadCrumbsForMilestoneBuilder
     */
    private $bread_crumbs_for_milestone_builder;

    public function __construct(
        ProjectManager $project_manager,
        Planning_MilestoneFactory $milestone_factory,
        Planning_MilestonePaneFactory $pane_factory,
        Planning_VirtualTopMilestonePaneFactory $top_milestone_pane_factory,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder,
        VisitRecorder $visit_recorder,
        AllBreadCrumbsForMilestoneBuilder $bread_crumbs_for_milestone_builder
    ) {
        $this->project_manager                    = $project_manager;
        $this->milestone_factory                  = $milestone_factory;
        $this->pane_factory                       = $pane_factory;
        $this->top_milestone_pane_factory         = $top_milestone_pane_factory;
        $this->service_crumb_builder              = $service_crumb_builder;
        $this->top_milestone_crumb_builder        = $top_milestone_crumb_builder;
        $this->visit_recorder                     = $visit_recorder;
        $this->bread_crumbs_for_milestone_builder = $bread_crumbs_for_milestone_builder;
    }

    public function getMilestoneController(Codendi_Request $request)
    {
        return new Planning_MilestoneController(
            $request,
            $this->milestone_factory,
            $this->project_manager,
            $this->pane_factory,
            $this->visit_recorder,
            $this->bread_crumbs_for_milestone_builder
        );
    }

    public function getVirtualTopMilestoneController(Codendi_Request $request)
    {
        return new Planning_VirtualTopMilestoneController(
            $request,
            $this->milestone_factory,
            $this->project_manager,
            $this->top_milestone_pane_factory,
            $this->service_crumb_builder,
            $this->top_milestone_crumb_builder
        );
    }
}
