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
 */

namespace Tuleap\AgileDashboard\Kanban;

use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_PermissionsManager;
use Codendi_Request;
use Feedback;
use KanbanPresenter;
use Project;
use TrackerFactory;
use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\Kanban\RecentlyVisited\RecentlyVisitedKanbanDao;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

class ShowKanbanController extends BaseController
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
    /**
     * @var AgileDashboardCrumbBuilder
     */
    private $agile_dashboard_crumb_builder;
    /**
     * @var BreadCrumbBuilder
     */
    private $kanban_crumb_builder;
    /**
     * @var RecentlyVisitedKanbanDao
     */
    private $recently_visited_dao;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager,
        AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        BreadCrumbBuilder $kanban_crumb_builder,
        RecentlyVisitedKanbanDao $recently_visited_dao,
    ) {
        parent::__construct('agiledashboard', $request);

        $this->project                       = $this->request->getProject();
        $this->kanban_factory                = $kanban_factory;
        $this->tracker_factory               = $tracker_factory;
        $this->permissions_manager           = $permissions_manager;
        $this->agile_dashboard_crumb_builder = $agile_dashboard_crumb_builder;
        $this->kanban_crumb_builder          = $kanban_crumb_builder;
        $this->recently_visited_dao          = $recently_visited_dao;
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
    {
        $kanban_id = (int) $this->request->get('id');
        $user      = $this->request->getCurrentUser();

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->agile_dashboard_crumb_builder->build(
                $this->getCurrentUser(),
                $this->project
            )
        );

        try {
            $breadcrumbs->addBreadCrumb($this->kanban_crumb_builder->build($user, $kanban_id));
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
            $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
            if (! $user->isAnonymous()) {
                $this->recently_visited_dao->save((int) $user->getId(), (int) $kanban_id, (int) $_SERVER['REQUEST_TIME']);
            }

            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }

            $user_is_kanban_admin = $this->permissions_manager->userCanAdministrate(
                $user,
                $tracker->getGroupId()
            );

            $filter_tracker_report_id = (int) $this->request->get('tracker_report_id');
            $dashboard_widget_id      = 0;

            return $this->renderToString(
                'kanban',
                new KanbanPresenter(
                    $kanban,
                    $user,
                    $user_is_kanban_admin,
                    $user->getShortLocale(),
                    (int) $tracker->getGroupId(),
                    $dashboard_widget_id,
                    $filter_tracker_report_id,
                )
            );
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-agiledashboard', 'Kanban not found.')
            );
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'error_perm_denied')
            );
        }
    }
}
