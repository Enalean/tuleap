<?php
/**
  * Copyright 1999-2000 (c) The SourceForge Crew
  * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\Admin\Homepage\UserCounterDao;
use Tuleap\Dashboard\User\UserDashboardDeletor;
use Tuleap\Dashboard\User\UserDashboardRouter;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardUpdator;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardSaver;
use Tuleap\Dashboard\User\WidgetDeletor;
use Tuleap\Dashboard\User\WidgetMinimizor;
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
use Tuleap\Layout\IncludeAssets;

require_once('pre.php');
require_once('my_utils.php');
require_once('common/event/EventManager.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('../admin/admin_utils.php');

$request = HTTPRequest::instance();

$hp    = Codendi_HTMLPurifier::instance();
$title = $Language->getText(
    'my_index',
        'title',
        array(
            $hp->purify(user_getrealname(user_getid()),
            CODENDI_PURIFIER_CONVERT_HTML) .' ('.user_getname().')'
        )
);
$csrf_token                 = new CSRFSynchronizerToken('/my/');
$dashboard_widget_dao       = new DashboardWidgetDao();
$user_dashboard_dao         = new UserDashboardDao($dashboard_widget_dao);
$dashboard_widget_retriever = new DashboardWidgetRetriever($dashboard_widget_dao);
$router                     = new UserDashboardRouter(
    new UserDashboardController(
        $csrf_token,
        $title,
        new UserDashboardRetriever($user_dashboard_dao),
        new UserDashboardSaver($user_dashboard_dao),
        new UserDashboardDeletor($user_dashboard_dao),
        new UserDashboardUpdator($user_dashboard_dao),
        $dashboard_widget_retriever,
        new DashboardWidgetPresenterBuilder(),
        new WidgetDeletor($dashboard_widget_dao),
        new WidgetMinimizor($dashboard_widget_dao),
        new IncludeAssets(ForgeConfig::get('tuleap_dir').'/src/www/assets', '/assets')
    ),
    new WidgetDashboardController(
        $csrf_token,
        new WidgetCreator(
            $dashboard_widget_dao
        ),
        $dashboard_widget_retriever,
        new DashboardWidgetReorder(
            $dashboard_widget_dao,
            $dashboard_widget_retriever,
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

if (! ForgeConfig::get('sys_use_tlp_in_dashboards')) {
    Tuleap\Instrument\Collect::increment('service.my.accessed');

    // Make sure this page is not cached because
    // it uses the exact same URL for all user's
    // personal page
    header("Cache-Control: no-cache, no-store, must-revalidate"); // for HTTP 1.1
    header("Pragma: no-cache");  // for HTTP 1.0

    my_header(array('title' => $title, 'body_class' => array('widgetable')));

    if (user_is_super_user()) {
        $builder = new NbUsersByStatusBuilder(new UserCounterDao());
        $nb_users_by_status = $builder->getNbUsersByStatusBuilder();
        echo site_admin_warnings($nb_users_by_status);
    }

    echo '<p>' . $Language->getText('my_index', 'message') . '</p>';

    $lm = new WidgetLayoutManager();
    $lm->displayLayout(user_getid(), WidgetLayoutManager::OWNER_TYPE_USER);

    if (!$current_user->getPreference(Tuleap_Tour_WelcomeTour::TOUR_NAME)) {
        $GLOBALS['Response']->addTour(new Tuleap_Tour_WelcomeTour($current_user));
    }

    if ($request->get('pv') == 2) {
        $GLOBALS['Response']->pv_footer(array());
    } else {
        site_footer(array());
    }
}
