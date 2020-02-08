<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

namespace Tuleap\Dashboard\User;

use HTTPRequest;
use Tuleap\Dashboard\Widget\WidgetDashboardController;

class UserDashboardRouter
{
    public const DASHBOARD_TYPE = 'user';

    /** @var UserDashboardController  */
    private $user_dashboard_controller;
    /**
     * @var WidgetDashboardController
     */
    private $widget_dashboard_controller;

    public function __construct(
        UserDashboardController $user_dashboard_controller,
        WidgetDashboardController $widget_dashboard_controller
    ) {
        $this->user_dashboard_controller   = $user_dashboard_controller;
        $this->widget_dashboard_controller = $widget_dashboard_controller;
    }

    /**
     * Routes the request to the correct controller
     * @return void
     */
    public function route(HTTPRequest $request)
    {
        if (! $request->getCurrentUser()->isLoggedIn()) {
            return;
        }

        $action = $request->get('action');

        switch ($action) {
            case 'minimize-widget':
                $this->user_dashboard_controller->minimizeWidget($request);
                break;
            case 'maximize-widget':
                $this->user_dashboard_controller->maximizeWidget($request);
                break;
            case 'delete-widget':
                $this->user_dashboard_controller->deleteWidget($request);
                break;
            case 'reorder-widgets':
                $this->widget_dashboard_controller->reorderWidgets($request, self::DASHBOARD_TYPE);
                break;
            case 'add-dashboard':
                $this->user_dashboard_controller->createDashboard($request);
                break;
            case 'delete-dashboard':
                $this->user_dashboard_controller->deleteDashboard($request);
                break;
            case 'edit-dashboard':
                $this->user_dashboard_controller->editDashboard($request);
                break;
            case 'edit-widget-line':
                $this->widget_dashboard_controller->editWidgetLine($request, self::DASHBOARD_TYPE);
                break;
            default:
                $this->user_dashboard_controller->display($request);
                break;
        }
    }
}
