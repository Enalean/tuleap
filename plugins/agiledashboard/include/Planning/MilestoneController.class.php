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
        $this->redirectToCorrectPane();
        return $this->renderToString(
            'show',
            $this->getMilestonePresenter()
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
        return $this->pane_factory->getActivePane($this->milestone)->getIdentifier();
    }

    public function getHeaderOptions() {
        $this->generateBareMilestone();
        $pane_info_identifier = new AgileDashboard_PaneInfoIdentifier();

        return array(
            Layout::INCLUDE_FAT_COMBINED => ! $pane_info_identifier->isPaneAPlanningV2(
                $this->getActivePaneIdentifier()
            )
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
            $breadcrumbs_merger->push(new BreadCrumb_VirtualTopMilestone($plugin_path, $this->project));
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

    public function solveInconsistencies() {
        $milestone_artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('aid'));
        $milestone          = $this->milestone_factory->getMilestoneFromArtifact($milestone_artifact);
        $artifact_ids       = $this->request->get('inconsistent-artifacts-ids');
        $extractor          = new AgileDashboard_PaneRedirectionExtractor();

        if (! ($this->inconsistentArtifactsIdsAreValid($artifact_ids) && $milestone->solveInconsistencies($this->getCurrentUser(), $artifact_ids)) ) {
            $this->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_agiledashboard', 'error_on_inconsistencies_solving'));
        }

        $this->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_agiledashboard', 'successful_inconsistencies_solving'));

        if (! $request_has_redirect = $extractor->getRedirectToParameters($this->request, $this->project)) {
            $this->redirect(array(
                'group_id' => $this->project->getGroupId()
            ));
        }

        $this->redirect($extractor->getRedirectToParameters($this->request, $this->project));
    }

    private function inconsistentArtifactsIdsAreValid(array $artifact_ids) {
        $validator        = new Valid_UInt();
        $validator->required();
        $artifact_factory = Tracker_ArtifactFactory::instance();

        foreach ($artifact_ids as $artifact_id) {
            if (! ($validator->validate($artifact_id) && $artifact_factory->getArtifactById($artifact_id)) ) {
                return false;
            }
        }
        return true;
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
