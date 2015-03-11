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

class AgileDashboard_KanbanColumnManager {

    /**
     * @var AgileDashboard_KanbanColumnDao
     */
    private $column_dao;

    /** @var AgileDashboard_PermissionsManager */
    private $permissions_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(
        AgileDashboard_KanbanColumnDao $column_dao,
        AgileDashboard_PermissionsManager $permissions_manager,
        TrackerFactory $tracker_factory
    ) {
        $this->column_dao          = $column_dao;
        $this->permissions_manager = $permissions_manager;
        $this->tracker_factory     = $tracker_factory;
    }

    /**
     * @throws AgileDashboard_UserNotAdminException
     *
     * @return bool
     */
    public function setColumnWipLimit(PFUser $user, AgileDashboard_Kanban $kanban, AgileDashboard_KanbanColumn $column, $wip_limit) {
        $project_id = $this->tracker_factory->getTrackerById($kanban->getTrackerId())->getGroupId();

        if (! $this->permissions_manager->userCanAdministrate($user, $project_id)) {
            throw new AgileDashboard_UserNotAdminException($user);
        }

        return $this->column_dao->setColumnWipLimit($column->getKanbanId(), $column->getId(), $wip_limit);
    }
}
