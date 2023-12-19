<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

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

    /** @var Project */
    private $project;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var AllBreadCrumbsForMilestoneBuilder
     */
    private $bread_crumbs_for_milestone_builder;
    /**
     * @var HeaderOptionsProvider
     */
    private $header_options_provider;

    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        ProjectManager $project_manager,
        Planning_MilestonePaneFactory $pane_factory,
        VisitRecorder $visit_recorder,
        AllBreadCrumbsForMilestoneBuilder $bread_crumbs_for_milestone_builder,
        HeaderOptionsProvider $header_options_provider,
    ) {
        parent::__construct('agiledashboard', $request);
        $this->milestone_factory                  = $milestone_factory;
        $this->pane_factory                       = $pane_factory;
        $this->project                            = $project_manager->getProject($request->get('group_id'));
        $this->visit_recorder                     = $visit_recorder;
        $this->bread_crumbs_for_milestone_builder = $bread_crumbs_for_milestone_builder;
        $this->header_options_provider            = $header_options_provider;
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function show(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $this->generateBareMilestone();
        if (! $this->milestone->getArtifact()) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-agiledashboard', 'Unable to find milestone'));
            $GLOBALS['Response']->redirect(AGILEDASHBOARD_BASE_URL . '/?group_id=' . urlencode((string) $this->project->getID()));
        }
        $this->redirectToCorrectPane();
        $this->recordVisit();

        $presenter_data = $this->pane_factory->getPanePresenterData($this->milestone);
        $template_name  = $this->getTemplateName($presenter_data);

        $title = dgettext('tuleap-agiledashboard', 'View Planning');

        $active_pane = $presenter_data->getActivePane();
        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProjectWithActivePromotedItem(
                    $this->project,
                    \AgileDashboardPlugin::PLUGIN_SHORTNAME,
                    $this->milestone->getPromotedMilestoneId(),
                )
                ->withBodyClass($active_pane->getBodyClass())
                ->withFatCombined($active_pane->shouldIncludeFatCombined())
                ->withNewDropdownLinkSection(
                    $this->header_options_provider
                        ->getCurrentContextSection($this->request->getCurrentUser(), $this->milestone, $active_pane->getIdentifier())
                        ->unwrapOr(null),
                )
                ->build()
        );
        echo $this->renderToString(
            $template_name,
            $this->getMilestonePresenter($presenter_data)
        );
        $displayFooter();
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
        return $this->pane_factory->getActivePane($this->milestone, $this->request->getCurrentUser())->getIdentifier();
    }

    private function getMilestonePresenter(PanePresenterData $presenter_data)
    {
        return new AgileDashboard_MilestonePresenter($this->milestone, $presenter_data);
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
    {
        $this->generateBareMilestone();

        return $this->bread_crumbs_for_milestone_builder->getBreadcrumbs(
            $this->getCurrentUser(),
            $this->project,
            $this->milestone
        );
    }

    public function solveInconsistencies()
    {
        $milestone_artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('aid'));
        $milestone          = $this->milestone_factory->getMilestoneFromArtifact($milestone_artifact);
        $artifact_ids       = $this->request->get('inconsistent-artifacts-ids');
        $extractor          = new AgileDashboard_PaneRedirectionExtractor();

        if (! ($this->inconsistentArtifactsIdsAreValid($artifact_ids) && $milestone->solveInconsistencies($this->getCurrentUser(), $artifact_ids))) {
            $this->addFeedback(Feedback::ERROR, dgettext('tuleap-agiledashboard', 'An error occurred while trying to solve inconsistencies.'));
        }

        $this->addFeedback(Feedback::INFO, dgettext('tuleap-agiledashboard', 'Inconsistencies successfully solved!'));

        if (! $request_has_redirect = $extractor->getRedirectToParameters($this->request, $this->project)) {
            $this->redirect([
                'group_id' => $this->project->getGroupId(),
            ]);
        }

        $this->redirect($extractor->getRedirectToParameters($this->request, $this->project));
    }

    private function inconsistentArtifactsIdsAreValid(array $artifact_ids)
    {
        $validator = new Valid_UInt();
        $validator->required();
        $artifact_factory = Tracker_ArtifactFactory::instance();

        foreach ($artifact_ids as $artifact_id) {
            if (! ($validator->validate($artifact_id) && $artifact_factory->getArtifactById($artifact_id))) {
                return false;
            }
        }
        return true;
    }

    private function generateBareMilestone()
    {
        $this->milestone = $this->milestone_factory->getBareMilestone(
            $this->getCurrentUser(),
            $this->project,
            $this->request->get('planning_id'),
            $this->request->get('aid')
        );
    }

    /**
     * @return string
     */
    private function getTemplateName(PanePresenterData $presenter_data)
    {
        $current_pane_identifier = $presenter_data->getActivePane()->getIdentifier();
        if (
            $current_pane_identifier === DetailsPaneInfo::IDENTIFIER ||
            $current_pane_identifier === PlanningV2PaneInfo::IDENTIFIER
        ) {
            return 'show';
        }

        return 'show-flaming-parrot';
    }

    private function recordVisit(): void
    {
        $artifact = $this->milestone->getArtifact();
        if ($artifact === null) {
            return;
        }

        $this->visit_recorder->record($this->getCurrentUser(), $artifact);
    }
}
