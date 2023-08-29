<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Option\Option;

/**
 * Handles the HTTP actions related to a planning milestone.
 */
final class VirtualTopMilestoneController extends BaseController
{
    /** @var Option<\Planning_VirtualTopMilestone> */
    private Option $milestone;
    private \Project $project;

    /**
     * Instanciates a new controller.
     *
     * TODO:
     *   - pass $request to actions (e.g. show).
     *
     */
    public function __construct(
        \Codendi_Request $request,
        \Planning_MilestoneFactory $milestone_factory,
        \ProjectManager $project_manager,
        private readonly VirtualTopMilestonePresenterBuilder $presenter_builder,
        private readonly AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        private readonly VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder,
        private readonly HeaderOptionsProvider $header_options_provider,
        private readonly SplitKanbanConfigurationChecker $flag_checker,
    ) {
        parent::__construct('agiledashboard', $request);
        $this->project = $project_manager->getProject($request->get('group_id'));
        try {
            $this->milestone = Option::fromValue(
                $milestone_factory->getVirtualTopMilestone($this->getCurrentUser(), $this->project)
            );
        } catch (\Planning_NoPlanningsException) {
            $this->milestone = Option::nothing(\Planning_VirtualTopMilestone::class);
        }
    }

    public function showTop(): string
    {
        if ($this->milestone->isNothing() && ! $this->flag_checker->isProjectAllowedToUseSplitKanban($this->project)) {
            $query_parts = ['group_id' => $this->request->get('group_id')];
            $this->redirect($query_parts);
        }
        $presenter = $this->presenter_builder->buildPresenter(
            $this->milestone,
            $this->project,
            $this->getCurrentUser()
        );
        return $this->renderToString('show-top', $presenter);
    }

    public function getHeaderOptions(\PFUser $user): array
    {
        return $this->milestone->mapOr(
            fn($milestone) => $this->header_options_provider->getHeaderOptions(
                $user,
                $milestone,
                PlanningV2PaneInfo::IDENTIFIER
            ),
            []
        );
    }

    public function getBreadcrumbs(): BreadCrumbCollection
    {
        $breadcrumb_dropdowns = new BreadCrumbCollection();
        $breadcrumb_dropdowns->addBreadCrumb(
            $this->agile_dashboard_crumb_builder->build($this->getCurrentUser(), $this->project)
        );
        if (! $this->flag_checker->isProjectAllowedToUseSplitKanban($this->project)) {
            $breadcrumb_dropdowns->addBreadCrumb(
                $this->top_milestone_crumb_builder->build($this->project)
            );
        }

        return $breadcrumb_dropdowns;
    }
}
