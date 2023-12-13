<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Project;

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use Project;
use ProjectManager;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Dashboard\AssetsIncluder;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Project\FirstTimerPresenterBuilder;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Project\ProjectDashboardRouter;
use Tuleap\Dashboard\Project\ProjectDashboardSaver;
use Tuleap\Dashboard\Project\RecentlyVisitedProjectDashboardDao;
use Tuleap\Dashboard\Project\WidgetDeletor;
use Tuleap\Dashboard\Widget\WidgetMinimizor;
use Tuleap\Dashboard\Widget\DashboardWidgetChecker;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDeletor;
use Tuleap\Dashboard\Widget\DashboardWidgetLineUpdater;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenterBuilder;
use Tuleap\Dashboard\Widget\DashboardWidgetRemoverInList;
use Tuleap\Dashboard\Widget\DashboardWidgetReorder;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\Dashboard\Widget\WidgetDashboardController;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

class Home implements DispatchableWithRequest, DispatchableWithProject
{
    public function __construct(private ProjectManager $project_manager)
    {
    }

    /**
     * @param array $variables
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project  = $this->getProject($variables);
        $group_id = $project->getId();

        //set up the group_id
        $_REQUEST['group_id']        = $_GET['group_id'] = $group_id;
        $request->params['group_id'] = $_REQUEST['group_id'];

        if ($request->isAjax() && ! $request->existAndNonEmpty('action')) {
            $layout->sendJSON(['id' => $group_id, 'name' => $project->getPublicName()]);
            exit;
        }
        // if the summary service is active we display the dashboard of the project
        // otherwise we display the first active service on the list

        if ($project->usesService('summary')) {
            \Tuleap\Project\ServiceInstrumentation::increment('dashboard');
            $widget_factory = new WidgetFactory(
                UserManager::instance(),
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                EventManager::instance()
            );

            $core_assets = new \Tuleap\Layout\IncludeCoreAssets();

            $csrf_token                 = new CSRFSynchronizerToken('/project/');
            $dashboard_widget_dao       = new DashboardWidgetDao($widget_factory);
            $dashboard_widget_retriever = new DashboardWidgetRetriever($dashboard_widget_dao);
            $project_dashboard_dao      = new ProjectDashboardDao($dashboard_widget_dao);
            $router                     = new ProjectDashboardRouter(
                new ProjectDashboardController(
                    $csrf_token,
                    $project,
                    new ProjectDashboardRetriever($project_dashboard_dao),
                    new ProjectDashboardSaver(
                        $project_dashboard_dao,
                        new RecentlyVisitedProjectDashboardDao(),
                    ),
                    new DashboardWidgetRetriever(
                        $dashboard_widget_dao
                    ),
                    new DashboardWidgetPresenterBuilder(
                        $widget_factory,
                        new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao())
                    ),
                    new WidgetDeletor($dashboard_widget_dao),
                    new WidgetMinimizor(),
                    new AssetsIncluder(
                        $layout,
                        $core_assets,
                        new CssAssetCollection([new CssAssetWithoutVariantDeclinaisons($core_assets, 'dashboards-style')])
                    ),
                    EventManager::instance(),
                    $layout,
                    new JavascriptViteAsset(
                        new IncludeViteAssets(
                            __DIR__ . '/../../scripts/project-registration/frontend-assets',
                            '/assets/core/project-registration'
                        ),
                        'src/index-for-modal.ts'
                    ),
                    Codendi_HTMLPurifier::instance(),
                    new FirstTimerPresenterBuilder(
                        new InvitationDao(
                            new SplitTokenVerificationStringHasher(),
                            new \Tuleap\InviteBuddy\InvitationInstrumentation(\Tuleap\Instrument\Prometheus\Prometheus::instance())
                        ),
                        UserManager::instance(),
                    ),
                    new RecentlyVisitedProjectDashboardDao(),
                ),
                new WidgetDashboardController(
                    $csrf_token,
                    new WidgetCreator(
                        $dashboard_widget_dao
                    ),
                    $dashboard_widget_retriever,
                    new DashboardWidgetReorder(
                        $dashboard_widget_dao,
                        new DashboardWidgetRemoverInList()
                    ),
                    new DashboardWidgetChecker($dashboard_widget_dao),
                    new DashboardWidgetDeletor($dashboard_widget_dao),
                    new DashboardWidgetLineUpdater(
                        $dashboard_widget_dao
                    )
                )
            );
            $router->route($request);
        } else {
            $service = null;

            foreach ($project->getServices() as $containedSrv) {
                if ($containedSrv->isUsed()) {
                    $service = $containedSrv;
                    break;
                }
            }

            if ($service === null || $service->isIFrame()) {
                $layout->addFeedback(\Feedback::ERROR, _('A service displayed in a iframe cannot be the first service of a project'));
                $layout->redirect('/project/' . urlencode((string) $project->getID()) . '/admin/services');
            } else {
                $layout->redirect($service->getUrl());
            }
        }
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectFromAutocompleter($variables['name']);

        if ($project === false) {
            throw new NotFoundException(_('Project does not exist'));
        }

        return $project;
    }
}
