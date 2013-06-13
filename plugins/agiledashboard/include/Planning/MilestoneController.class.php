<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
class Planning_MilestoneController extends MVC2_PluginController {

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Planning_Milestone
     */
    private $milestone;


    /** @var Planning_MilestonePaneFactory */
    private $pane_factory;

    /** @var AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory */
    private $pane_presenter_builder_factory;

    /** @var Project */
    private $project;

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
        Planning_MilestonePaneFactory $pane_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory
    ) {
        parent::__construct('agiledashboard', $request);
        $this->milestone_factory              = $milestone_factory;
        $this->pane_factory                   = $pane_factory;
        $this->pane_presenter_builder_factory = $pane_presenter_builder_factory;
        $this->project = $project_manager->getProject($request->get('group_id'));
    }

    public function show() {
        $this->generateBareMilestone();
        $this->render(
            'show',
            $this->getMilestonePresenter()
        );
    }

    private function getMilestonePresenter() {
        $redirect_parameter = new Planning_MilestoneRedirectParameter();
        
        return new AgileDashboard_MilestonePresenter(
            $this->milestone,
            $this->getCurrentUser(),
            $this->request,
            $this->pane_factory->getPanePresenterData($this->milestone),
            $redirect_parameter->getPlanningRedirectToNew($this->milestone, $this->pane_factory->getDefaultPaneIdentifier())
        );
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        $this->generateBareMilestone();

        if ($this->milestone->getArtifact()) {
            $breadcrumbs_merger = new BreadCrumb_Merger();
            foreach(array_reverse($this->milestone->getAncestors()) as $milestone) {
                $breadcrumbs_merger->push(new BreadCrumb_Milestone($plugin_path, $milestone));
            }
            $breadcrumbs_merger->push(new BreadCrumb_Milestone($plugin_path, $this->milestone));
            return $breadcrumbs_merger;
        }
        
        return new BreadCrumb_NoCrumb();
    }

    public function submilestonedata() {
        $this->generateBareMilestone();
        $this->render('submilestone-content', $this->getSubmilestonePresenter());
    }

    private function getSubmilestonePresenter() {
        $presenter_builder = $this->pane_presenter_builder_factory->getSubmilestonePresenterBuilder();
        
        return $presenter_builder->getSubmilestonePresenter($this->getCurrentUser(), $this->milestone);
    }

    private function generateBareMilestone() {
        $this->milestone = $this->milestone_factory->getBareMilestone(
            $this->getCurrentUser(),
            $this->project,
            $this->request->get('planning_id'),
            $this->request->get('aid')
        );
    }
}

?>
