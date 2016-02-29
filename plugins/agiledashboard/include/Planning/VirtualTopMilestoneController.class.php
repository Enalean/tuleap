<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
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
require_once 'common/mvc2/PluginController.class.php';

/**
 * Handles the HTTP actions related to a planning milestone.
 */
class Planning_VirtualTopMilestoneController extends MVC2_PluginController {

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var Planning_Milestone */
    private $milestone;

    /** @var Project */
    private $project;

    /** @var Planning_VirtualTopMilestonePaneFactory */
    private $top_milestone_pane_factory;

    /**
     * Instanciates a new controller.
     *
     * TODO:
     *   - pass $request to actions (e.g. show).
     *
     * @param Codendi_Request           $request
     * @param PlanningFactory           $planning_factory
     * @param Planning_MilestoneFactory $milestone_factory
     */
    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        ProjectManager $project_manager,
        Planning_VirtualTopMilestonePaneFactory $top_milestone_pane_factory
    ) {
        parent::__construct('agiledashboard', $request);
        $this->milestone_factory              = $milestone_factory;
        $this->top_milestone_pane_factory     = $top_milestone_pane_factory;
        $this->project = $project_manager->getProject($request->get('group_id'));
    }

    public function showTop() {
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

    private function redirectToCorrectPane() {
        $current_pane_identifier = $this->getActivePaneIdentifier();
        if ($current_pane_identifier !== $this->request->get('pane')) {
            $this->request->set('pane', $current_pane_identifier);
            $this->redirect($this->request->params);
        }
    }

    private function getActivePaneIdentifier() {
        return $this->top_milestone_pane_factory->getActivePane($this->milestone)->getIdentifier();
    }

    public function getHeaderOptions() {
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

    private function getTopMilestonePresenter() {
        $redirect_parameter = new Planning_MilestoneRedirectParameter();

        return new AgileDashboard_MilestonePresenter(
            $this->milestone,
            $this->getCurrentUser(),
            $this->request,
            $this->top_milestone_pane_factory->getPanePresenterData($this->milestone),
            $redirect_parameter->getPlanningRedirectToNew(
                $this->milestone,
                $this->top_milestone_pane_factory->getDefaultPaneIdentifier()
            )
        );
    }

    private function generateVirtualTopMilestone() {
        $this->milestone = $this->milestone_factory->getVirtualTopMilestone(
            $this->getCurrentUser(),
            $this->project
        );
    }
}
