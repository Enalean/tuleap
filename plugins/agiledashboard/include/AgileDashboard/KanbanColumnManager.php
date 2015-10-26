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
        $project_id = $this->getProjectIdForKanban($kanban);

        $this->checkUserCanAdministrate($user, $project_id);

        return $this->column_dao->setColumnWipLimit($column->getKanbanId(), $column->getId(), $wip_limit);
    }

    public function reorderColumns(PFUser $user, AgileDashboard_Kanban $kanban, array $column_ids) {
        $project_id = $this->getProjectIdForKanban($kanban);

        $this->checkUserCanAdministrate($user, $project_id);

        $semantic = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            throw new Kanban_SemanticStatus_Not_DefinedException();
        }

        if (! $semantic->isFieldBoundToStaticValues()) {
            throw new Kanban_SemanticStatus_Not_Bound_To_Static_ValuesException();
        }

        $this->checkAllColumnsAreProvided($semantic, $column_ids);

        return $semantic->getField()->getBind()->getValueDao()->reorder($column_ids);
    }

    private function checkAllColumnsAreProvided(Tracker_Semantic_Status $semantic, array $column_ids) {
        $all_open_values     = $semantic->getOpenValues();
        $values_not_provided = array_diff($all_open_values, $column_ids);
        $values_not_open     = array_diff($column_ids, $all_open_values);

        if (! empty($values_not_provided)) {
            throw new Kanban_SemanticStatus_AllColumnIdsNotProvidedException();
        }

        if (! empty($values_not_open)) {
            throw new Kanban_SemanticStatus_ColumnIdsNotInOpenSemanticException();
        }
    }

    public function createColumn(PFUser $user, AgileDashboard_Kanban $kanban, $label) {
        $project_id = $this->getProjectIdForKanban($kanban);

        $this->checkUserCanAdministrate($user, $project_id);

        $semantic = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            throw new Kanban_SemanticStatus_Not_DefinedException();
        }

        if (! $semantic->isFieldBoundToStaticValues()) {
            throw new Kanban_SemanticStatus_Not_Bound_To_Static_ValuesException();
        }

        return $semantic->addOpenValue($label);
    }

    /**
     * @return int
     */
    private function getProjectIdForKanban(AgileDashboard_Kanban $kanban) {
        return $this->tracker_factory->getTrackerById($kanban->getTrackerId())->getGroupId();
    }

    private function checkUserCanAdministrate($user, $project_id) {
        if (! $this->permissions_manager->userCanAdministrate($user, $project_id)) {
            throw new AgileDashboard_UserNotAdminException($user);
        }
    }

    /**
     * @return Tracker_Semantic_Status
     */
    private function getSemanticStatus(AgileDashboard_Kanban $kanban) {
        $tracker = $this->getTrackerForKanban($kanban);
        if (! $tracker) {
            return;
        }

        $semantic = Tracker_Semantic_Status::load($tracker);
        if (! $semantic->getFieldId()) {
            return;
        }

        return $semantic;
    }

    private function getTrackerForKanban(AgileDashboard_Kanban $kanban) {
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            throw new RestException(500, 'The tracker used by the kanban does not exist anymore');
        }

        return $tracker;
    }
}
