<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use AgileDashboard_PermissionsManager;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use AgileDashboard_KanbanColumnDao;
use AgileDashboard_KanbanColumnManager;
use AgileDashboard_KanbanColumnNotFoundException;
use AgileDashboard_UserNotAdminException;
use AgileDashboardStatisticsAggregator;
use TrackerFactory;
use UserManager;
use PFUser;
use AgileDashboard_KanbanUserPreferences;

class KanbanColumnsResource {

    const MAX_LIMIT = 100;

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var AgileDashboard_KankanColumnFactory */
    private $kanban_column_factory;

    /** @var AgileDashboard_KanbanColumnManager */
    private $kanban_column_manager;

    /** @var AgileDashboardStatisticsAggregator */
    private $statistics_aggregator;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct() {
        $this->tracker_factory = TrackerFactory::instance();

        $this->kanban_factory = new AgileDashboard_KanbanFactory(
            $this->tracker_factory,
            new AgileDashboard_KanbanDao()
        );

        $kanban_column_dao           = new AgileDashboard_KanbanColumnDao();
        $permissions_manager         = new AgileDashboard_PermissionsManager();
        $this->kanban_column_factory = new AgileDashboard_KanbanColumnFactory(
            $kanban_column_dao,
            new AgileDashboard_KanbanUserPreferences()
        );
        $this->kanban_column_manager = new AgileDashboard_KanbanColumnManager(
            $kanban_column_dao,
            $permissions_manager,
            $this->tracker_factory
        );

        $this->statistics_aggregator = new AgileDashboardStatisticsAggregator();
    }

    /**
     * @url OPTIONS
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     */
    public function options() {
        Header::allowOptionsPatch();
    }

    /**
     * Update column
     *
     * Change a column properties (wip_limit for now)
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}
     *
     * @param int $id           Id of the column
     * @param int $kanban_id    Id of the Kanban {@from query}
     * @param int $wip_limit    The new wip limit {@from body} {@type int}
     */
    protected function patch($id, $kanban_id, $wip_limit) {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $kanban_id);

        try{
            $column = $this->kanban_column_factory->getColumnForAKanban($kanban, $id, $current_user);
            if (! $this->kanban_column_manager->setColumnWipLimit($current_user, $kanban, $column, $wip_limit)) {
                throw new RestException(500);
            }
        } catch (AgileDashboard_KanbanColumnNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (AgileDashboard_UserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch (AgileDashboard_SemanticStatusNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        $this->statistics_aggregator->addWIPModificationHit(
            $this->getProjectIdForKanban($kanban)
        );
    }

    /** @return AgileDashboard_Kanban */
    private function getKanban(PFUser $user, $id) {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $id);
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        return $kanban;
    }

    private function getCurrentUser() {
        $user = UserManager::instance()->getCurrentUser();

        return $user;
    }

    /**
     * @return int
     */
    private function getProjectIdForKanban(AgileDashboard_Kanban $kanban) {
        return $this->tracker_factory->getTrackerById($kanban->getTrackerId())->getGroupId();
    }
}
