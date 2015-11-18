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

    /** @var AgileDashboard_KanbanActionsChecker */
    private $kanban_actions_checker;

    public function __construct(
        AgileDashboard_KanbanColumnDao $column_dao,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker
    ) {
        $this->column_dao             = $column_dao;
        $this->kanban_actions_checker = $kanban_actions_checker;
    }

    /**
     * @throws AgileDashboard_UserNotAdminException
     *
     * @return bool
     */
    public function setColumnWipLimit(PFUser $user, AgileDashboard_Kanban $kanban, AgileDashboard_KanbanColumn $column, $wip_limit) {
        $this->kanban_actions_checker->checkUserCanAdministrate($user, $kanban);

        return $this->column_dao->setColumnWipLimit($column->getKanbanId(), $column->getId(), $wip_limit);
    }

    public function createColumn(PFUser $user, AgileDashboard_Kanban $kanban, $label) {
        $this->kanban_actions_checker->checkUserCanAddColumns($user, $kanban);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        return $semantic->addOpenValue($label);
    }

    public function reorderColumns(PFUser $user, AgileDashboard_Kanban $kanban, array $column_ids) {
        $this->kanban_actions_checker->checkUserCanReorderColumns($user, $kanban);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        $this->checkAllColumnsAreProvided($semantic, $column_ids);

        return $semantic->getField()->getBind()->getValueDao()->reorder($column_ids);
    }

    private function checkAllColumnsAreProvided(Tracker_Semantic_Status $semantic, array $column_ids) {
        $all_open_values     = $semantic->getOpenValues();
        $values_not_provided = array_diff($all_open_values, $column_ids);
        $values_not_open     = array_diff($column_ids, $all_open_values);

        if (! empty($values_not_provided)) {
            throw new Kanban_SemanticStatusAllColumnIdsNotProvidedException();
        }

        if (! empty($values_not_open)) {
            throw new Kanban_SemanticStatusColumnIdsNotInOpenSemanticException();
        }
    }
}
