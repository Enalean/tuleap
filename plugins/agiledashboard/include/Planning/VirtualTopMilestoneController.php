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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

/**
 * Handles the HTTP actions related to a planning milestone.
 */
final class VirtualTopMilestoneController extends BaseController
{
    private \Planning_Milestone $milestone;
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
        private readonly \Planning_MilestoneFactory $milestone_factory,
        \ProjectManager $project_manager,
        private readonly \Planning_VirtualTopMilestonePaneFactory $top_milestone_pane_factory,
        private readonly AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        private readonly VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder,
        private readonly HeaderOptionsProvider $header_options_provider,
    ) {
        parent::__construct('agiledashboard', $request);
        $this->project = $project_manager->getProject($request->get('group_id'));
    }

    public function showTop(): string
    {
        try {
            $this->generateVirtualTopMilestone();
        } catch (\Planning_NoPlanningsException) {
            $query_parts = ['group_id' => $this->request->get('group_id')];
            $this->redirect($query_parts);
        }

        $this->redirectToCorrectPane();

        return $this->renderToString('show-top', $this->getTopMilestonePresenter());
    }

    private function redirectToCorrectPane(): void
    {
        $current_pane_identifier = $this->getActivePaneIdentifier();
        if ($current_pane_identifier !== $this->request->get('pane')) {
            $this->request->set('pane', $current_pane_identifier);
            $this->redirect($this->request->params);
        }
    }

    private function getActivePaneIdentifier(): string
    {
        return $this->top_milestone_pane_factory->getActivePane($this->milestone)->getIdentifier();
    }

    public function getHeaderOptions(\PFUser $user): array
    {
        try {
            $this->generateVirtualTopMilestone();
            $identifier = $this->getActivePaneIdentifier();

            return $this->header_options_provider->getHeaderOptions($user, $this->milestone, $identifier);
        } catch (\Planning_NoPlanningsException) {
            return [];
        }
    }

    private function getTopMilestonePresenter(): \AgileDashboard_MilestonePresenter
    {
        return new \AgileDashboard_MilestonePresenter(
            $this->milestone,
            $this->top_milestone_pane_factory->getPanePresenterData($this->milestone)
        );
    }

    /**
     * @throws \Planning_NoPlanningsException
     */
    private function generateVirtualTopMilestone(): void
    {
        $this->milestone = $this->milestone_factory->getVirtualTopMilestone(
            $this->getCurrentUser(),
            $this->project
        );
    }

    public function getBreadcrumbs(): BreadCrumbCollection
    {
        $breadcrumb_dropdowns = new BreadCrumbCollection();
        $breadcrumb_dropdowns->addBreadCrumb(
            $this->agile_dashboard_crumb_builder->build($this->getCurrentUser(), $this->project)
        );
        $breadcrumb_dropdowns->addBreadCrumb(
            $this->top_milestone_crumb_builder->build($this->project)
        );

        return $breadcrumb_dropdowns;
    }
}
