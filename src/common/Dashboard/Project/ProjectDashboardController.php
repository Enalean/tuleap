<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;
use EventManager;
use Exception;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\Dashboard\AssetsIncluder;
use Tuleap\Dashboard\DashboardDoesNotExistException;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenterBuilder;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Dashboard\Widget\OwnerInfo;
use Tuleap\Dashboard\Widget\WidgetMinimizor;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\TroveCat\TroveCatLinkDao;

class ProjectDashboardController
{
    public const DASHBOARD_TYPE        = 'project';
    public const LEGACY_DASHBOARD_TYPE = 'g';

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;
    /**
     * @var ProjectDashboardRetriever
     */
    private $retriever;
    /**
     * @var ProjectDashboardSaver
     */
    private $saver;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var DashboardWidgetRetriever
     */
    private $widget_retriever;
    /**
     * @var DashboardWidgetPresenterBuilder
     */
    private $widget_presenter_builder;
    /**
     * @var WidgetDeletor
     */
    private $widget_deletor;
    /**
     * @var WidgetMinimizor
     */
    private $widget_minimizor;
    /**
     * @var AssetsIncluder
     */
    private $assets_includer;

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var BaseLayout
     */
    private $layout;

    private Codendi_HTMLPurifier $purifier;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Project $project,
        ProjectDashboardRetriever $retriever,
        ProjectDashboardSaver $saver,
        DashboardWidgetRetriever $widget_retriever,
        DashboardWidgetPresenterBuilder $widget_presenter_builder,
        WidgetDeletor $widget_deletor,
        WidgetMinimizor $widget_minimizor,
        AssetsIncluder $assets_includer,
        EventManager $event_manager,
        BaseLayout $layout,
        private JavascriptAssetGeneric $project_registration_assets,
        Codendi_HTMLPurifier $purifier,
        private FirstTimerPresenterBuilder $first_timer_presenter_builder,
        private readonly RecentlyVisitedProjectDashboardDao $recently_visited_project_dashboard_dao,
    ) {
        $this->csrf                     = $csrf;
        $this->project                  = $project;
        $this->retriever                = $retriever;
        $this->saver                    = $saver;
        $this->widget_retriever         = $widget_retriever;
        $this->widget_presenter_builder = $widget_presenter_builder;
        $this->widget_deletor           = $widget_deletor;
        $this->widget_minimizor         = $widget_minimizor;
        $this->assets_includer          = $assets_includer;
        $this->event_manager            = $event_manager;
        $this->layout                   = $layout;
        $this->purifier                 = $purifier;
    }

    public function display(HTTPRequest $request)
    {
        $project            = $request->getProject();
        $user               = $request->getCurrentUser();
        $dashboard_id       = (int) $request->get('dashboard_id');
        $project_dashboards = $this->retriever->getAllProjectDashboards($this->project);

        $display_project_created_modal_presenter = $this->event_manager->dispatch(
            new DisplayCreatedProjectModalPresenter(
                $request->get("should-display-created-project-modal"),
                $project,
                $request->get('xml-template-name')
            )
        );
        \assert($display_project_created_modal_presenter instanceof DisplayCreatedProjectModalPresenter);

        if ($display_project_created_modal_presenter->should_display_created_project_modal) {
            $this->layout->addJavascriptAsset($this->project_registration_assets);
        }

        if ($dashboard_id && ! $this->doesDashboardIdExist($dashboard_id, $project_dashboards)) {
            $this->layout->addFeedback(
                Feedback::ERROR,
                _('The requested dashboard does not exist.')
            );
        }

        $reversed_project_dashboards = array_reverse($project_dashboards);
        $first_dashboard             = array_pop($reversed_project_dashboards);
        if (($dashboard_id || $first_dashboard) && ! $user->isAnonymous()) {
            $this->recently_visited_project_dashboard_dao->save(
                (int) $user->getId(),
                $dashboard_id ?: (int) $first_dashboard->getId(),
                \Tuleap\Request\RequestTime::getTimestamp(),
            );
        }

        $this->event_manager->dispatch(new ProjectDashboardIsDisplayed($this->layout));

        $project_dashboards_presenter = $this->getProjectDashboardsPresenter(
            $user,
            $project,
            $dashboard_id,
            $project_dashboards
        );
        $trove_cats                   = [];
        if (ForgeConfig::get('sys_use_trove')) {
            $trove_dao = new TroveCatLinkDao();
            foreach ($trove_dao->searchTroveCatForProject($project->getID()) as $row_trovecat) {
                $trove_cats[] = $row_trovecat['fullname'];
            }
        }

        $this->assets_includer->includeAssets($project_dashboards_presenter);

        $first_timer_presenter = $this->first_timer_presenter_builder->buildPresenter($user, $project);
        if ($first_timer_presenter) {
            $this->layout->addJavascriptAsset($first_timer_presenter->javascript_assets);
        }

        $title = $this->purifier->purify($this->getPageTitle($project_dashboards_presenter, $project));

        site_project_header(
            $project,
            HeaderConfigurationBuilder::get($title)
                ->inProjectNotInBreadcrumbs($project, \Service::SUMMARY)
                ->withBodyClass(['reduce-help-button'])
                ->build()
        );
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('tuleap_dir') . '/src/templates/dashboard'
        );

        $event = new ProjectProviderEvent($this->project);
        $this->event_manager->processEvent($event);

        $project_icon = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($this->project->getIconUnicodeCodepoint());

        $renderer->renderToPage(
            'project',
            new ProjectPagePresenter(
                $this->csrf,
                '/projects/' . urlencode($this->project->getUnixName()) . '/',
                new ProjectPresenter(
                    $this->project,
                    ProjectManager::instance(),
                    $request->getCurrentUser(),
                    $trove_cats,
                    $project_icon
                ),
                $project_dashboards_presenter,
                $this->canUpdateDashboards($user, $project),
                $display_project_created_modal_presenter,
                $first_timer_presenter,
            )
        );
        $this->layout->footer([]);
    }

    public function createDashboard(HTTPRequest $request)
    {
        $this->csrf->check();

        $user    = $request->getCurrentUser();
        $project = $request->getProject();
        $name    = $request->get('dashboard-name');

        try {
            $dashboard_id = $this->saver->save($user, $project, $name);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                _('Dashboard has been successfully created.')
            );
            $this->redirectToDashboard($dashboard_id);
        } catch (UserCanNotUpdateProjectDashboardException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('You have not rights to update dashboards of the project "%s".'),
                    $project->getPublicName()
                )
            );
        } catch (NameDashboardAlreadyExistsException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('The dashboard "%s" already exists.'),
                    $name
                )
            );
        } catch (NameDashboardDoesNotExistException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('The name is missing for creating a dashboard.')
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('Dashboard creation failed.')
            );
        }

        $this->redirectToDefaultDashboard();
    }

    public function editDashboard(HTTPRequest $request)
    {
        $this->csrf->check();

        $user           = $request->getCurrentUser();
        $project        = $request->getProject();
        $dashboard_id   = $request->get('dashboard-id');
        $dashboard_name = $request->get('dashboard-name');

        try {
            $this->saver->update($user, $project, $dashboard_id, $dashboard_name);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Dashboard has been successfully updated.'
                )
            );
        } catch (UserCanNotUpdateProjectDashboardException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('You have not rights to update dashboards of the project "%s".'),
                    $project->getPublicName()
                )
            );
        } catch (NameDashboardAlreadyExistsException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext(
                        'tuleap-core',
                        'The dashboard "%s" already exists.'
                    ),
                    $dashboard_name
                )
            );
        } catch (NameDashboardDoesNotExistException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'The name is missing for editing the dashboard.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Cannot update the requested dashboard.'
                )
            );
        }

        $this->redirectToDashboard($dashboard_id);
    }

    public function deleteDashboard(HTTPRequest $request)
    {
        $this->csrf->check();

        $user         = $request->getCurrentUser();
        $project      = $request->getProject();
        $dashboard_id = $request->get('dashboard-id');

        try {
            $this->saver->delete($user, $project, $dashboard_id);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Dashboard has been successfully deleted.'
                )
            );
        } catch (UserCanNotUpdateProjectDashboardException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('You have not rights to update dashboards of the project "%s".'),
                    $project->getPublicName()
                )
            );
        } catch (DashboardDoesNotExistException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'The requested dashboard does not exist.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Cannot delete the requested dashboard.'
                )
            );
        }

        $this->redirectToDefaultDashboard();
    }

    /**
     * @param $dashboard_id
     * @param ProjectDashboard[] $project_dashboards
     * @return ProjectDashboardPresenter[]
     */
    private function getProjectDashboardsPresenter(PFUser $user, Project $project, $dashboard_id, array $project_dashboards): array
    {
        $project_dashboards_presenter = [];

        foreach ($project_dashboards as $index => $dashboard) {
            if (! $dashboard_id && $index === 0) {
                $is_active = true;
            } else {
                $is_active = $dashboard->getId() === $dashboard_id;
            }

            $widgets_presenter = [];
            if ($is_active) {
                $widgets_lines = $this->widget_retriever->getAllWidgets($dashboard->getId(), self::DASHBOARD_TYPE);
                if ($widgets_lines) {
                    $widgets_presenter = $this->widget_presenter_builder->getWidgetsPresenter(
                        $dashboard,
                        OwnerInfo::createForProject($project),
                        $widgets_lines,
                        $this->canUpdateDashboards($user, $project),
                        $user,
                    );
                }
            }

            $project_dashboards_presenter[] = new ProjectDashboardPresenter($dashboard, $is_active, $widgets_presenter);
        }

        return $project_dashboards_presenter;
    }

    /**
     * @param ProjectDashboard[] $project_dashboards
     */
    private function doesDashboardIdExist(int $dashboard_id, array $project_dashboards): bool
    {
        foreach ($project_dashboards as $dashboard) {
            if ($dashboard_id === $dashboard->getId()) {
                return true;
            }
        }

        return false;
    }

    private function redirectToDefaultDashboard()
    {
        $GLOBALS['Response']->redirect('/projects/' . urlencode($this->project->getUnixName()) . '/');
    }

    private function redirectToDashboard($dashboard_id)
    {
        $GLOBALS['Response']->redirect(
            '/projects/' . urlencode($this->project->getUnixName()) . '/?dashboard_id=' . urlencode($dashboard_id)
        );
    }

    /**
     * @return bool
     */
    private function canUpdateDashboards(PFUser $user, Project $project)
    {
        return $user->isAdmin($project->getID());
    }

    public function deleteWidget(HTTPRequest $request)
    {
        $this->csrf->check();

        $user         = $request->getCurrentUser();
        $project      = $request->getProject();
        $dashboard_id = $request->get('dashboard-id');
        $widget_id    = $request->get('widget-id');

        try {
            $this->widget_deletor->delete($user, $project, $dashboard_id, self::DASHBOARD_TYPE, $widget_id);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Widget has been successfully deleted.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Cannot delete the widget.'
                )
            );
        }

        $this->redirectToDashboard($dashboard_id);
    }

    public function minimizeWidget(HTTPRequest $request)
    {
        $this->csrf->check();

        $user         = $request->getCurrentUser();
        $dashboard_id = $request->get('dashboard-id');
        $widget_id    = $request->get('widget-id');

        $this->widget_minimizor->minimize($user, (int) $widget_id);

        $this->redirectToDashboard($dashboard_id);
    }

    public function maximizeWidget(HTTPRequest $request)
    {
        $this->csrf->check();

        $user         = $request->getCurrentUser();
        $dashboard_id = $request->get('dashboard-id');
        $widget_id    = $request->get('widget-id');

        $this->widget_minimizor->maximize($user, (int) $widget_id);

        $this->redirectToDashboard($dashboard_id);
    }

    /**
     * @return string
     */
    private function getPageTitle($project_dashboards_presenter, Project $project)
    {
        $title = '';
        foreach ($project_dashboards_presenter as $presenter) {
            if ($presenter->is_active) {
                $title = $presenter->name . ' - ';
            }
        }
        $title .= $project->getPublicName();

        return $title;
    }
}
