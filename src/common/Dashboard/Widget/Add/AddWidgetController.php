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

use CSRFSynchronizerToken;
use DataAccessException;
use Exception;
use Feedback;
use HTTPRequest;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\Widget\WidgetFactory;
use Widget;

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
    /**
     * @var WidgetCreator
     */
    private $creator;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $disabled_project_widgets_checker;

    public function __construct(
        DashboardWidgetDao $dao,
        WidgetFactory $factory,
        WidgetCreator $creator,
        DisabledProjectWidgetsChecker $disabled_project_widgets_checker
    ) {
        $this->dao                              = $dao;
        $this->factory                          = $factory;
        $this->creator                          = $creator;
        $this->disabled_project_widgets_checker = $disabled_project_widgets_checker;
    }

    public function display(HTTPRequest $request)
    {
        $dashboard_id   = $request->get('dashboard-id');
        $dashboard_type = $request->get('dashboard-type');
        $used_widgets   = $this->getUsedWidgets($dashboard_id, $dashboard_type);

        try {
            $this->checkThatDashboardBelongsToTheOwner($request, $dashboard_type, $dashboard_id);
            $this->displayWidgetEntries($dashboard_type, $used_widgets);
        } catch (DataAccessException $exception) {
            $GLOBALS['Response']->send400JSONErrors(_('We cannot find any widgets.'));
        }
    }

    public function create(HTTPRequest $request)
    {
        $dashboard_id   = $request->get('dashboard-id');
        $dashboard_type = $request->get('dashboard-type');
        $name           = $request->get('widget-name');

        $this->checkCSRF($dashboard_type);

        try {
            $this->checkThatDashboardBelongsToTheOwner($request, $dashboard_type, $dashboard_id);
            $widget = $this->factory->getInstanceByWidgetName($name);

            if ($this->disabled_project_widgets_checker->isWidgetDisabled($widget, $dashboard_type) === true) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('The widget is disabled in project dashboard.')
                );
                $this->redirectToDashboard($request, $dashboard_id, $dashboard_type);
                exit();
            }

            if (! $widget->isUnique() || ! $this->isUniqueWidgetAlreadyAddedInDashboard($widget, $dashboard_id, $dashboard_type)) {
                $this->creator->create(
                    $this->getOwnerIdByDashboardType($request, $dashboard_type),
                    $this->factory->getOwnerTypeByDashboardType($dashboard_type),
                    $dashboard_id,
                    $widget,
                    $request
                );

                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    _('The widget has been added successfully')
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('The widget is already used')
                );
            }
        } catch (Exception $exception) {
            if ($exception->getMessage()) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $exception->getMessage()
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('An error occurred while trying to add the widget to the dashboard')
                );
            }
        }
        $this->redirectToDashboard($request, $dashboard_id, $dashboard_type);
    }

    private function isUniqueWidgetAlreadyAddedInDashboard(Widget $widget, $dashboard_id, $dashboard_type)
    {
        $used_widgets = $this->getUsedWidgets($dashboard_id, $dashboard_type);

        return $widget->isUnique() && in_array($widget->getId(), $used_widgets);
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
                if (
                    $widget
                    && $widget->isAvailable()
                    && $this->disabled_project_widgets_checker->isWidgetDisabled($widget, $dashboard_type) === false
                ) {
                    $widgets_presenter[] = new WidgetPresenter($widget, $widget->isUnique() && in_array($widget->getId(), $used_widgets));
                }
            }
            $widgets_category_presenter[] = new WidgetsByCategoryPresenter($category, $widgets_presenter);
        }
        $this->sortAlphabetically($widgets_category_presenter);

        $GLOBALS['Response']->sendJSON(array('widgets_categories' => $widgets_category_presenter));
    }

    private function sortAlphabetically(array &$widgets_category_presenter)
    {
        $this->sortCategoriesAlphabetically($widgets_category_presenter);
        $this->sortWidgetsAlphabetically($widgets_category_presenter);
    }

    private function sortCategoriesAlphabetically(array &$widgets_category_presenter)
    {
        $general = _('General');

        usort(
            $widgets_category_presenter,
            function (WidgetsByCategoryPresenter $a, WidgetsByCategoryPresenter $b) use ($general) {
                if ($a->name === $general) {
                    return -1;
                }

                if ($b->name === $general) {
                    return 1;
                }

                return strnatcasecmp($a->name, $b->name);
            }
        );
    }

    private function sortWidgetsAlphabetically(array &$widgets_category_presenter)
    {
        foreach ($widgets_category_presenter as $category) {
            usort(
                $category->widgets,
                function (WidgetPresenter $a, WidgetPresenter $b) {
                    return strnatcasecmp($a->name, $b->name);
                }
            );
        }
    }

    private function getWidgetsGroupedByCategories($dashboard_type)
    {
        $categories = array();
        $widgets    = $this->factory->getWidgetsForOwnerType($dashboard_type);
        foreach ($widgets as $widget) {
            if ($widget && $widget->isAvailable()) {
                $categories[$widget->getCategory()][$widget->getId()] = $widget;
            }
        }

        return $categories;
    }

    /**
     * @param $dashboard_type
     * @param $dashboard_id
     */
    private function checkThatDashboardBelongsToTheOwner(HTTPRequest $request, $dashboard_type, $dashboard_id)
    {
        $owner_id = $this->getOwnerIdByDashboardType($request, $dashboard_type);
        $this->dao->checkThatDashboardBelongsToTheOwner(
            $owner_id,
            $dashboard_type,
            $dashboard_id
        );
    }

    /**
     * @param $dashboard_type
     * @return int
     */
    private function getOwnerIdByDashboardType(HTTPRequest $request, $dashboard_type)
    {
        if ($dashboard_type === UserDashboardController::DASHBOARD_TYPE) {
            $owner_id = $request->getCurrentUser()->getId();
        } else {
            $owner_id = $request->getProject()->getID();
        }
        return $owner_id;
    }

    /**
     * @param $dashboard_type
     * @return string
     */
    private function getOwnerTypeByDashboardType($dashboard_type)
    {
        $owner_type = $dashboard_type === UserDashboardController::DASHBOARD_TYPE ?
            UserDashboardController::LEGACY_DASHBOARD_TYPE :
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE;
        return $owner_type;
    }

    /**
     * @param $dashboard_type
     */
    private function checkCSRF($dashboard_type)
    {
        if ($dashboard_type === ProjectDashboardController::DASHBOARD_TYPE) {
            $csrf = new CSRFSynchronizerToken('/project/');
        } else {
            $csrf = new CSRFSynchronizerToken('/my/');
        }

        $csrf->check();
    }

    /**
     * @param $dashboard_id
     * @param $dashboard_type
     */
    private function redirectToDashboard(HTTPRequest $request, $dashboard_id, $dashboard_type)
    {
        if ($dashboard_type === ProjectDashboardController::DASHBOARD_TYPE) {
            $url = '/projects/' . $request->getProject()->getUnixName() . '/';
        } else {
            $url = '/my/';
        }

        $GLOBALS['Response']->redirect(
            $url . '?' . http_build_query(
                array(
                    'dashboard_id' => $dashboard_id
                )
            )
        );
    }

    /**
     * @param $dashboard_id
     * @param $dashboard_type
     * @return array
     */
    private function getUsedWidgets($dashboard_id, $dashboard_type)
    {
        $used_widgets = array();
        foreach ($this->dao->searchUsedWidgetsContentByDashboardId($dashboard_id, $dashboard_type) as $row) {
            $used_widgets[] = $row['name'];
        }
        return $used_widgets;
    }
}
