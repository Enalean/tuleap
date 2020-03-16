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

use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use ProjectManager;
use Tuleap\Dashboard\AssetsIncluder;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Project\ProjectDashboardRouter;
use Tuleap\Dashboard\Project\ProjectDashboardSaver;
use Tuleap\Dashboard\Project\WidgetDeletor;
use Tuleap\Dashboard\Project\WidgetMinimizor;
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
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

class Home implements DispatchableWithRequest
{
    /**
     * @param array $args
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $args)
    {
        $project = ProjectManager::instance()->getProjectFromAutocompleter($args['name']);
        if ($project && !$project->isError()) {
            $group_id = $project->getId();

            //set up the group_id
            $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
            $request->params['group_id'] = $_REQUEST['group_id'];

            if ($request->isAjax() && ! $request->existAndNonEmpty('action')) {
                header('Content-type: application/json');
                echo json_encode(
                    array(
                        'id' => $group_id,
                        'name' => $project->getPublicName(),
                    )
                );
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

                $project_registration_creation_javascript_asset = new IncludeAssets(
                    __DIR__ . '/../../www/assets/project-registration/creation/scripts',
                    '/assets/project-registration/creation/scripts'
                );

                $project_registration_creation_css_assets = new CssAsset(
                    new IncludeAssets(
                        __DIR__ . '/../../www/assets/project-registration/creation/themes',
                        '/assets/project-registration/creation/themes'
                    ),
                    'project-registration-creation'
                );

                $csrf_token                   = new CSRFSynchronizerToken('/project/');
                $dashboard_widget_dao         = new DashboardWidgetDao($widget_factory);
                $dashboard_widget_retriever   = new DashboardWidgetRetriever($dashboard_widget_dao);
                $project_dashboard_dao        = new ProjectDashboardDao($dashboard_widget_dao);
                $router                       = new ProjectDashboardRouter(
                    new ProjectDashboardController(
                        $csrf_token,
                        $project,
                        new ProjectDashboardRetriever($project_dashboard_dao),
                        new ProjectDashboardSaver($project_dashboard_dao),
                        new DashboardWidgetRetriever(
                            $dashboard_widget_dao
                        ),
                        new DashboardWidgetPresenterBuilder(
                            $widget_factory,
                            new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao())
                        ),
                        new WidgetDeletor($dashboard_widget_dao),
                        new WidgetMinimizor($dashboard_widget_dao),
                        new AssetsIncluder(
                            $layout,
                            new IncludeAssets(__DIR__ . '/../../www/assets', '/assets'),
                            new CssAssetCollection(
                                [new CssAsset(
                                    new IncludeAssets(
                                        __DIR__ . '/../../www/assets/dashboards/themes',
                                        '/assets/dashboards/themes'
                                    ),
                                    'dashboards'
                                )]
                            )
                        ),
                        EventManager::instance(),
                        $layout,
                        $project_registration_creation_javascript_asset,
                        $project_registration_creation_css_assets
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
        } else {
            throw new NotFoundException(_('Project does not exist'));
        }
    }
}
