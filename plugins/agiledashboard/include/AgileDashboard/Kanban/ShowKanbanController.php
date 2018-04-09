<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban;

use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_PermissionsManager;
use BreadCrumb_AgileDashboard;
use BreadCrumb_BreadCrumbGenerator;
use BreadCrumb_Merger;
use Codendi_Request;
use Feedback;
use KanbanPresenter;
use MVC2_PluginController;
use Project;
use TrackerFactory;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

class ShowKanbanController extends MVC2_PluginController
{
    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var AgileDashboard_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var Project
     */
    private $project;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager
    ) {
        parent::__construct('agiledashboard', $request);

        $this->project             = $this->request->getProject();
        $this->kanban_factory      = $kanban_factory;
        $this->tracker_factory     = $tracker_factory;
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @param string $plugin_path
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs($plugin_path)
    {
        $kanban_id   = $this->request->get('id');
        $user        = $this->request->getCurrentUser();

        $breadcrumbs = new BreadCrumbCollection();
        $agiledashboard_crumb_builder = new AgileDashboardCrumbBuilder();
        $breadcrumbs->addBreadCrumb(
            $agiledashboard_crumb_builder->build(
                $this->getCurrentUser(),
                $this->project,
                $plugin_path
            )
        );

        try {
            $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
            $kanban_crumb_builder = new BreadCrumbBuilder($plugin_path, $this->project, $kanban);
            $breadcrumbs->addBreadCrumb($kanban_crumb_builder->build());
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            // ignore, it will be catch in showKanban
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            // ignore, it will be catch in showKanban
        }

        return $breadcrumbs;
    }

    public function showKanban()
    {
        $kanban_id = $this->request->get('id');
        $user      = $this->request->getCurrentUser();

        try {
            $kanban  = $this->kanban_factory->getKanban($user, $kanban_id);
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());

            $user_is_kanban_admin = $this->permissions_manager->userCanAdministrate(
                $user,
                $tracker->getGroupId()
            );

            $filter_tracker_report_id = $this->request->get('tracker_report_id');
            $dashboard_widget_id      = 0;

            return $this->renderToString(
                'kanban',
                new KanbanPresenter(
                    $kanban,
                    $user,
                    $user_is_kanban_admin,
                    $user->getShortLocale(),
                    $tracker->getGroupId(),
                    $dashboard_widget_id,
                    $filter_tracker_report_id
                )
            );
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_not_found')
            );
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'error_perm_denied')
            );
        }
    }
}
