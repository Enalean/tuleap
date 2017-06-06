<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Dashboard\Widget\Add;

use DataAccessException;
use ForgeConfig;
use HTTPRequest;
use MustacheRenderer;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Widget\WidgetFactory;
use Widget;
use WidgetLayoutManager;

class AddWidgetController
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;
    /**
     * @var WidgetFactory
     */
    private $factory;

    public function __construct(
        DashboardWidgetDao $dao,
        WidgetFactory $factory
    ) {
        $this->dao     = $dao;
        $this->factory = $factory;
    }

    public function display(HTTPRequest $request)
    {
        $dashboard_id   = $request->get('dashboard-id');
        $dashboard_type = $request->get('dashboard-type');
        $used_widgets   = array();

        try {
            $this->checkThatDashboardBelongsToTheOwner($request, $dashboard_type, $dashboard_id);
            foreach ($this->dao->searchUsedWidgetsContentByDashboardId($dashboard_id, $dashboard_type) as $row) {
                $used_widgets[] = $row['name'];
            }
            $this->displayWidgetEntries($dashboard_type, $used_widgets);
        } catch (DataAccessException $exception) {
            $GLOBALS['Response']->send400JSONErrors(_('We cannot find any widgets.'));
        }
    }

    private function displayWidgetEntries(
        $dashboard_type,
        array $used_widgets
    ) {
        $categories                 = $this->getWidgetsGroupedByCategories($dashboard_type);
        $widgets_category_presenter = array();

        foreach ($categories as $category => $widgets) {
            $widgets_presenter = array();
            foreach ($widgets as $widget) {
                $widget = $this->factory->getInstanceByWidgetName($widget->id);
                if ($widget && $widget->isAvailable()) {
                    $widgets_presenter[] = new WidgetPresenter($widget->getTitle(), $widget->isUnique() && in_array($widget->getId(), $used_widgets));
                }
            }
            $widgets_category_presenter[] = new WidgetsByCategoryPresenter($category, $widgets_presenter);
        }

        $GLOBALS['Response']->sendJSON(array('widgets_categories' => $widgets_category_presenter));
    }

    private function getWidgetsGroupedByCategories($dashboard_type)
    {
        $categories = array();
        $owner_type = $dashboard_type === UserDashboardController::DASHBOARD_TYPE ?
            WidgetLayoutManager::OWNER_TYPE_USER :
            WidgetLayoutManager::OWNER_TYPE_GROUP;
        $widgets    = Widget::getWidgetsForOwnerType($owner_type);
        foreach ($widgets as $widget_name) {
            $widget = $this->factory->getInstanceByWidgetName($widget_name);
            if ($widget && $widget->isAvailable()) {
                $categories[$widget->getCategory()][$widget_name] = $widget;
            }
        }

        return $categories;
    }

    /**
     * @param HTTPRequest $request
     * @param $dashboard_type
     * @param $dashboard_id
     */
    private function checkThatDashboardBelongsToTheOwner(HTTPRequest $request, $dashboard_type, $dashboard_id)
    {
        if ($dashboard_type === UserDashboardController::DASHBOARD_TYPE) {
            $owner_id = $request->getCurrentUser()->getId();
        } else {
            $owner_id = $request->getProject()->getID();
        }
        $this->dao->checkThatDashboardBelongsToTheOwner(
            $owner_id,
            $dashboard_type,
            $dashboard_id
        );
    }
}
