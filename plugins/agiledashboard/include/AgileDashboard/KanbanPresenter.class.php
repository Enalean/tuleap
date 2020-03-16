<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportBuilder;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\AgileDashboard\Widget\WidgetAddToDashboardDropdownRepresentationBuilder;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Widget\WidgetFactory;

class KanbanPresenter
{
    /** @var string */
    public $language;

    /** @var string json of Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentationBuilder */
    public $kanban_representation;

    /** @var string json of Tuleap\AgileDashboard\Widget\WidgetAddToDashboardDropdownRepresentationBuilder */
    public $dashboard_dropdown_representation;

    /** @var bool */
    public $user_is_kanban_admin;

    /** @var int */
    public $project_id;

    /** @var int */
    public $user_id;

    /** @var string */
    public $view_mode;

    /** @var string */
    public $nodejs_server;

    /** @var int */
    public $widget_id;

    /**
     * @var array
     */
    public $tracker_reports;

    /** @var string */
    public $user_accessibility_mode;

    public function __construct(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        $user_is_kanban_admin,
        $language,
        $project_id,
        $dashboard_widget_id,
        $selected_tracker_report_id
    ) {
        $user_preferences              = new AgileDashboard_KanbanUserPreferences();
        $kanban_representation_builder = new Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentationBuilder(
            $user_preferences,
            new AgileDashboard_KanbanColumnFactory(
                new AgileDashboard_KanbanColumnDao(),
                $user_preferences
            ),
            new AgileDashboard_KanbanActionsChecker(
                TrackerFactory::instance(),
                new AgileDashboard_PermissionsManager(),
                Tracker_FormElementFactory::instance()
            )
        );

        $widget_factory    = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );
        $widget_dao              = new DashboardWidgetDao($widget_factory);
        $widget_dropdown_builder = new WidgetAddToDashboardDropdownRepresentationBuilder(
            new UserDashboardRetriever(
                new UserDashboardDao($widget_dao)
            ),
            new ProjectDashboardRetriever(new ProjectDashboardDao($widget_dao))
        );
        $project_manager        = ProjectManager::instance();
        $tracker_report_factory = Tracker_ReportFactory::instance();
        $tracker_report_builder = new TrackerReportBuilder(
            $tracker_report_factory,
            $kanban,
            new TrackerReportDao()
        );

        $this->widget_id                         = json_encode($dashboard_widget_id);
        $this->kanban_representation             = json_encode($kanban_representation_builder->build($kanban, $user));
        $this->dashboard_dropdown_representation = json_encode($widget_dropdown_builder->build($kanban, $user, $project_manager->getProject($project_id)));
        $this->tracker_reports                   = json_encode($tracker_report_builder->build($selected_tracker_report_id));
        $this->user_is_kanban_admin              = (int) $user_is_kanban_admin;
        $this->language                          = $language;
        $this->project_id                        = $project_id;
        $this->user_id                           = $user->getId();
        $this->view_mode                         = $user->getPreference('agiledashboard_kanban_item_view_mode_' . $kanban->getId());
        $this->nodejs_server                     = ForgeConfig::get('nodejs_server');
        $this->kanban_url                        = AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
            array(
                'group_id' => $this->project_id,
                'action'   => 'showKanban',
                'id'       => $kanban->getId()
            )
        );
        $this->user_accessibility_mode = json_encode((bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE));
    }
}
