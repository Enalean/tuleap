<?php
/**
  * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
  * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\Dashboard\AssetsIncluder;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardDeletor;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardRouter;
use Tuleap\Dashboard\User\UserDashboardSaver;
use Tuleap\Dashboard\User\UserDashboardUpdator;
use Tuleap\Dashboard\User\WidgetDeletor;
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
use Tuleap\Dashboard\Widget\WidgetMinimizor;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\Widget\WidgetFactory;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/my_utils.php';
require_once __DIR__ . '/../admin/admin_utils.php';

$request = HTTPRequest::instance();

$widget_factory = new WidgetFactory(
    UserManager::instance(),
    new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
    EventManager::instance()
);

$csrf_token                 = new CSRFSynchronizerToken('/my/');
$dashboard_widget_dao       = new DashboardWidgetDao($widget_factory);
$user_dashboard_dao         = new \Tuleap\Dashboard\User\DashboardDao();
$legacy_user_dashboard_dao  = new UserDashboardDao($dashboard_widget_dao);
$dashboard_widget_retriever = new DashboardWidgetRetriever($dashboard_widget_dao);
$core_assets                = new \Tuleap\Layout\IncludeCoreAssets();
$router                     = new UserDashboardRouter(
    new UserDashboardController(
        $csrf_token,
        new UserDashboardRetriever($legacy_user_dashboard_dao),
        new UserDashboardSaver($user_dashboard_dao, $legacy_user_dashboard_dao),
        new UserDashboardDeletor($legacy_user_dashboard_dao),
        new UserDashboardUpdator($user_dashboard_dao, $legacy_user_dashboard_dao),
        $dashboard_widget_retriever,
        new DashboardWidgetPresenterBuilder(
            $widget_factory,
            new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao())
        ),
        new WidgetDeletor($dashboard_widget_dao),
        new WidgetMinimizor(),
        new AssetsIncluder(
            $GLOBALS['Response'],
            $core_assets,
            new CssAssetCollection([new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($core_assets, 'dashboards-style')])
        ),
        new \Tuleap\Dashboard\User\FirstTimerPresenterBuilder(
            new \Tuleap\InviteBuddy\InvitationDao(
                new \Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher(),
                new \Tuleap\InviteBuddy\InvitationInstrumentation(\Tuleap\Instrument\Prometheus\Prometheus::instance())
            ),
            UserManager::instance(),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        ),
        new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
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

if (! user_isloggedin()) {
    exit_not_logged_in();
}
