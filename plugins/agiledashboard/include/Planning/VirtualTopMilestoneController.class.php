<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

/**
 * Handles the HTTP actions related to a planning milestone.
 */
class Planning_VirtualTopMilestoneController extends BaseController
{
    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var Planning_Milestone */
    private $milestone;

    /** @var Project */
    private $project;

    /** @var Planning_VirtualTopMilestonePaneFactory */
    private $top_milestone_pane_factory;

    /** @var AgileDashboardCrumbBuilder */
    private $agile_dashboard_crumb_builder;

    /** @var VirtualTopMilestoneCrumbBuilder */
    private $top_milestone_crumb_builder;

    /**
     * Instanciates a new controller.
     *
     * TODO:
     *   - pass $request to actions (e.g. show).
     *
     */
    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        ProjectManager $project_manager,
        Planning_VirtualTopMilestonePaneFactory $top_milestone_pane_factory,
        AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder
    ) {
        parent::__construct('agiledashboard', $request);
        $this->milestone_factory             = $milestone_factory;
        $this->top_milestone_pane_factory    = $top_milestone_pane_factory;
        $this->project                       = $project_manager->getProject($request->get('group_id'));
        $this->agile_dashboard_crumb_builder = $agile_dashboard_crumb_builder;
        $this->top_milestone_crumb_builder   = $top_milestone_crumb_builder;
    }

    public function showTop()
    {
        try {
            $this->generateVirtualTopMilestone();
        } catch (Planning_NoPlanningsException $e) {
            $query_parts = array('group_id' => $this->request->get('group_id'));
            $this->redirect($query_parts);
        }

        $this->redirectToCorrectPane();

        return $this->renderToString(
            'show-top',
            $this->getTopMilestonePresenter()
        );
    }

    private function redirectToCorrectPane()
    {
        $current_pane_identifier = $this->getActivePaneIdentifier();
        if ($current_pane_identifier !== $this->request->get('pane')) {
            $this->request->set('pane', $current_pane_identifier);
            $this->redirect($this->request->params);
        }
    }

    private function getActivePaneIdentifier()
    {
        return $this->top_milestone_pane_factory->getActivePane($this->milestone)->getIdentifier();
    }

    public function getHeaderOptions()
    {
        try {
            $this->generateVirtualTopMilestone();
            $pane_info_identifier = new AgileDashboard_PaneInfoIdentifier();

            return array(
                Layout::INCLUDE_FAT_COMBINED => ! $pane_info_identifier->isPaneAPlanningV2(
                    $this->getActivePaneIdentifier()
                )
            );
        } catch (Planning_NoPlanningsException $e) {
            return array();
        }
    }

    private function getTopMilestonePresenter()
    {
        return new AgileDashboard_MilestonePresenter(
            $this->milestone,
            $this->top_milestone_pane_factory->getPanePresenterData($this->milestone)
        );
    }

    private function generateVirtualTopMilestone()
    {
        $this->milestone = $this->milestone_factory->getVirtualTopMilestone(
            $this->getCurrentUser(),
            $this->project
        );
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
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
