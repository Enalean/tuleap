<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use AgileDashboard_BacklogItemDao;
use AgileDashboard_ConfigurationManager;
use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use MilestoneReportCriterionDao;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminSection;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeUpdater;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\AgileDashboard\ServiceAdministration\RedirectURI;
use Tuleap\AgileDashboard\ServiceAdministration\ScrumConfigurationUpdater;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\ForbiddenException;

class AdminController extends BaseController
{
    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var EventManager */
    private $event_manager;

    /** @var Project */
    private $project;

    /** @var AgileDashboardCrumbBuilder */
    private $service_crumb_builder;

    /** @var AdministrationCrumbBuilder */
    private $admin_crumb_builder;

    /**
     * @var CountElementsModeChecker
     */
    private $count_elements_mode_checker;
    /**
     * @var ScrumPresenterBuilder
     */
    private $scrum_presenter_builder;

    /**
     * @var GetAdditionalScrumAdminSection
     */
    private $additional_scrum_sections;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        AgileDashboard_ConfigurationManager $config_manager,
        EventManager $event_manager,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $admin_crumb_builder,
        CountElementsModeChecker $count_elements_mode_checker,
        ScrumPresenterBuilder $scrum_presenter_builder,
        private readonly BaseLayout $layout,
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id                    = (int) $this->request->get('group_id');
        $this->project                     = $this->request->getProject();
        $this->planning_factory            = $planning_factory;
        $this->config_manager              = $config_manager;
        $this->event_manager               = $event_manager;
        $this->service_crumb_builder       = $service_crumb_builder;
        $this->admin_crumb_builder         = $admin_crumb_builder;
        $this->count_elements_mode_checker = $count_elements_mode_checker;
        $this->scrum_presenter_builder     = $scrum_presenter_builder;

        $this->additional_scrum_sections = new GetAdditionalScrumAdminSection($this->project);
        $this->event_manager->dispatch($this->additional_scrum_sections);
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $this->getCurrentUser(),
                $this->project
            )
        );
        $breadcrumbs->addBreadCrumb(
            $this->admin_crumb_builder->build($this->project)
        );

        return $breadcrumbs;
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function adminScrum(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $this->checkScrumAccessIsNotBlocked();
        $this->layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/administration/frontend-assets',
                    '/assets/agiledashboard/administration'
                ),
                'src/main.ts'
            )
        );

        $title = dgettext('tuleap-agiledashboard', 'Scrum Administration of Backlog');

        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, \AgileDashboardPlugin::PLUGIN_SHORTNAME)
                ->build()
        );
        echo $this->renderToString(
            'admin-scrum',
            $this->scrum_presenter_builder->getAdminScrumPresenter(
                $this->getCurrentUser(),
                $this->project,
                $this->additional_scrum_sections
            )
        );
        $displayFooter();
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function adminCharts(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $this->checkScrumAccessIsNotBlocked();
        $title = dgettext("tuleap-agiledashboard", "Charts configuration");
        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, \AgileDashboardPlugin::PLUGIN_SHORTNAME)
                ->withBodyClass(['agiledashboard-body'])
                ->build()
        );
        echo $this->renderToString(
            "admin-charts",
            $this->getAdminChartsPresenter(
                $this->project
            )
        );
        $displayFooter();
    }

    public function updateConfiguration(): void
    {
        if (! $this->request->getCurrentUser()->isAdmin($this->group_id)) {
            $this->layout->addFeedback(
                \Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $token->check();

        $request_comes_from_homepage = false;
        $project                     = $this->request->getProject();

        $this->additional_scrum_sections->notifyAdditionalSectionsControllers(\HTTPRequest::instance());

        if ($this->request->exist("burnup-count-mode")) {
            $updater = new AgileDashboardChartsConfigurationUpdater(
                $this->request,
                new CountElementsModeUpdater(
                    new ProjectsCountModeDao()
                )
            );

            $updater->updateConfiguration();
            return;
        }

        $scrum_mono_milestone_dao = new ScrumForMonoMilestoneDao();
        $explicit_artifacts_dao   = new ArtifactsInExplicitBacklogDao();
        $explicit_backlog_dao     = new ExplicitBacklogDao();
        $updater                  = new ScrumConfigurationUpdater(
            $this->request,
            $this->config_manager,
            new ScrumForMonoMilestoneEnabler($scrum_mono_milestone_dao),
            new ScrumForMonoMilestoneDisabler($scrum_mono_milestone_dao),
            new ScrumForMonoMilestoneChecker($scrum_mono_milestone_dao, $this->planning_factory),
            new ConfigurationUpdater(
                $explicit_backlog_dao,
                new MilestoneReportCriterionDao(),
                new AgileDashboard_BacklogItemDao(),
                Planning_MilestoneFactory::build(),
                $explicit_artifacts_dao,
                new UnplannedArtifactsAdder(
                    $explicit_backlog_dao,
                    $explicit_artifacts_dao,
                    new PlannedArtifactDao()
                ),
                new AddToTopBacklogPostActionDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                $this->event_manager
            ),
            $this->event_manager,
            new MilestonesInSidebarDao(),
        );

        $updater->updateConfiguration();

        $this->layout->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-agiledashboard', 'Scrum configuration successfully updated.')
        );
        $redirect_uri = RedirectURI::buildScrumAdministration($project);
        $this->layout->redirect((string) $redirect_uri);
    }

    private function isScrumAccessible(): bool
    {
        $block_access_scrum = new BlockScrumAccess($this->project);
        $this->event_manager->dispatch($block_access_scrum);

        return $block_access_scrum->isScrumAccessEnabled();
    }

    private function checkScrumAccessIsNotBlocked(): void
    {
        if (! $this->isScrumAccessible()) {
            throw new ForbiddenException();
        }
    }

    private function getAdminChartsPresenter(Project $project): AdminChartsPresenter
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');

        return new AdminChartsPresenter(
            $project,
            $token,
            $this->count_elements_mode_checker->burnupMustUseCountElementsMode($project),
        );
    }
}
