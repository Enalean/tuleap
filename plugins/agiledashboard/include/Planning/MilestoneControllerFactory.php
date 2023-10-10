<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\AgileDashboard\CSRFSynchronizerTokenProvider;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

/**
 * I build MilestoneController
 */
class MilestoneControllerFactory
{
    public function __construct(
        private readonly \ProjectManager $project_manager,
        private readonly \Planning_MilestoneFactory $milestone_factory,
        private readonly \Planning_MilestonePaneFactory $pane_factory,
        private readonly VirtualTopMilestonePresenterBuilder $top_milestone_presenter_builder,
        private readonly AgileDashboardCrumbBuilder $service_crumb_builder,
        private readonly VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder,
        private readonly VisitRecorder $visit_recorder,
        private readonly AllBreadCrumbsForMilestoneBuilder $bread_crumbs_for_milestone_builder,
        private readonly HeaderOptionsProvider $header_options_provider,
        private readonly SplitKanbanConfigurationChecker $flag_checker,
        private readonly CSRFSynchronizerTokenProvider $token_provider,
    ) {
    }

    public function getMilestoneController(\Codendi_Request $request): \Planning_MilestoneController
    {
        return new \Planning_MilestoneController(
            $request,
            $this->milestone_factory,
            $this->project_manager,
            $this->pane_factory,
            $this->visit_recorder,
            $this->bread_crumbs_for_milestone_builder,
            $this->header_options_provider
        );
    }

    public function getVirtualTopMilestoneController(\Codendi_Request $request): VirtualTopMilestoneController
    {
        return new VirtualTopMilestoneController(
            $request,
            $this->milestone_factory,
            $this->project_manager,
            $this->top_milestone_presenter_builder,
            $this->service_crumb_builder,
            $this->top_milestone_crumb_builder,
            $this->flag_checker,
            $this->token_provider,
        );
    }
}
