<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

require_once 'common/mvc2/PluginController.class.php';

/**
 * Handles the HTTP actions related to a planning milestone.
 */
class Planning_MilestoneController extends BaseController
{
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
     * @var AgileDashboardCrumbBuilder
     */
    private $agile_dashboard_crumb_builder;
    /**
     * @var VirtualTopMilestoneCrumbBuilder
     */
    private $top_milestone_crumb_builder;
    /**
     * @var MilestoneCrumbBuilder
     */
    private $milestone_crumb_builder;

    /**
     * Instanciates a new controller.
     *
     * TODO:
     *   - pass $request to actions (e.g. show).
     *
     * @param Codendi_Request $request
     * @param Planning_MilestoneFactory $milestone_factory
     * @param ProjectManager $project_manager
     * @param Planning_MilestonePaneFactory $pane_factory
     * @param AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory
     * @param AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder
     * @param VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder
     * @param MilestoneCrumbBuilder $milestone_crumb_builder
     */
    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        ProjectManager $project_manager,
        Planning_MilestonePaneFactory $pane_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder,
        MilestoneCrumbBuilder $milestone_crumb_builder
    ) {
        parent::__construct('agiledashboard', $request);
        $this->milestone_factory              = $milestone_factory;
        $this->pane_factory                   = $pane_factory;
        $this->pane_presenter_builder_factory = $pane_presenter_builder_factory;
        $this->project                        = $project_manager->getProject($request->get('group_id'));
        $this->agile_dashboard_crumb_builder  = $agile_dashboard_crumb_builder;
        $this->top_milestone_crumb_builder    = $top_milestone_crumb_builder;
        $this->milestone_crumb_builder        = $milestone_crumb_builder;
    }

    public function show()
    {
        $this->generateBareMilestone();
        $this->redirectToCorrectPane();

        $presenter_data = $this->pane_factory->getPanePresenterData($this->milestone);
        $template_name  = $this->getTemplateName($presenter_data);

        return $this->renderToString(
            $template_name,
            $this->getMilestonePresenter($presenter_data)
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

    private function getMilestonePresenter(PanePresenterData $presenter_data)
    {
        $redirect_parameter = new Planning_MilestoneRedirectParameter();

        return new AgileDashboard_MilestonePresenter(
            $this->milestone,
            $this->getCurrentUser(),
            $this->request,
            $presenter_data,
            $redirect_parameter->getPlanningRedirectToNew($this->milestone, $this->pane_factory->getDefaultPaneIdentifier())
        );
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
    {
        $this->generateBareMilestone();

        $breadcrumbs            = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->agile_dashboard_crumb_builder->build($this->getCurrentUser(), $this->project)
        );
        $breadcrumbs->addBreadCrumb(
            $this->top_milestone_crumb_builder->build($this->project)
        );

        if ($this->milestone->getArtifact()) {
            foreach (array_reverse($this->milestone->getAncestors()) as $milestone) {
                $breadcrumbs->addBreadCrumb($this->milestone_crumb_builder->build($milestone));
            }
            $breadcrumbs->addBreadCrumb($this->milestone_crumb_builder->build($this->milestone));
        }

        return $breadcrumbs;
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

    private function generateBareMilestone() {
        $this->milestone = $this->milestone_factory->getBareMilestone(
            $this->getCurrentUser(),
            $this->project,
            $this->request->get('planning_id'),
            $this->request->get('aid')
        );
    }

    /**
     * @param PanePresenterData $presenter_data
     * @return string
     */
    private function getTemplateName(PanePresenterData $presenter_data)
    {
        $current_pane_identifier = $presenter_data->getActivePane()->getIdentifier();
        if ($current_pane_identifier === DetailsPaneInfo::IDENTIFIER ||
            $current_pane_identifier === PlanningV2PaneInfo::IDENTIFIER
        ) {
            return 'show';
        }

        return 'show-flaming-parrot';
    }
}
