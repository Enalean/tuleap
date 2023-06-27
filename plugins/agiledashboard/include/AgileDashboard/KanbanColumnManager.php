<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Kanban\KanbanColumnDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;

class AgileDashboard_KanbanColumnManager
{
    /** @var KanbanColumnDao */
    private $column_dao;

    /** @var BindStaticValueDao */
    private $formelement_field_list_bind_static_value_dao;

    /** @var AgileDashboard_KanbanActionsChecker */
    private $kanban_actions_checker;

    public function __construct(
        KanbanColumnDao $column_dao,
        BindStaticValueDao $formelement_field_list_bind_static_value_dao,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker,
    ) {
        $this->column_dao                                   = $column_dao;
        $this->formelement_field_list_bind_static_value_dao = $formelement_field_list_bind_static_value_dao;
        $this->kanban_actions_checker                       = $kanban_actions_checker;
    }

    /**
     * @throws SemanticStatusNotDefinedException
     */
    public function createColumn(PFUser $user, AgileDashboard_Kanban $kanban, string $label): ?int
    {
        $this->kanban_actions_checker->checkUserCanAddColumns($user, $kanban);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        return $semantic->addOpenValue($label);
    }

    public function reorderColumns(PFUser $user, AgileDashboard_Kanban $kanban, array $column_ids)
    {
        $this->kanban_actions_checker->checkUserCanReorderColumns($user, $kanban);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        $this->checkAllColumnsAreProvided($semantic, $column_ids);

        return $semantic->getField()?->getBind()->getValueDao()->reorder($column_ids);
    }

    /**
     * @throws AgileDashboard_KanbanColumnNotRemovableException
     * @throws Kanban_SemanticStatusBasedOnASharedFieldException
     * @throws Kanban_SemanticStatusNotBoundToStaticValuesException
     * @throws Kanban_SemanticStatusNotDefinedException
     * @throws Kanban_TrackerNotDefinedException
     */
    public function deleteColumn(PFUser $user, AgileDashboard_Kanban $kanban, AgileDashboard_KanbanColumn $column)
    {
        $this->kanban_actions_checker->checkUserCanDeleteColumn($user, $kanban, $column);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        $this->column_dao->deleteColumn(
            $column->getKanbanId(),
            $column->getId(),
            function () use ($semantic, $column) {
                return $semantic->removeOpenValue($column->getId())
                    && $this->hideColumnFromTrackerFieldStaticValues($column, $semantic);
            },
        );

        return true;
    }

    public function updateWipLimit(
        PFUser $user,
        AgileDashboard_Kanban $kanban,
        AgileDashboard_KanbanColumn $column,
        int $wip_limit,
    ): void {
        $this->kanban_actions_checker->checkUserCanAdministrate($user, $kanban);

        $this->column_dao->setColumnWipLimit($column->getKanbanId(), $column->getId(), $wip_limit);
    }

    public function updateLabel(PFUser $user, AgileDashboard_Kanban $kanban, AgileDashboard_KanbanColumn $column, $label)
    {
        $this->kanban_actions_checker->checkUserCanAdministrate($user, $kanban);
        $this->kanban_actions_checker->checkUserCanEditColumnLabel($user, $kanban);

        return $this->formelement_field_list_bind_static_value_dao->updateLabel($column->getId(), $label);
    }

    private function hideColumnFromTrackerFieldStaticValues(AgileDashboard_KanbanColumn $column, Tracker_Semantic_Status $semantic)
    {
        return $this->formelement_field_list_bind_static_value_dao->hideValue($column->getId());
    }

    private function checkAllColumnsAreProvided(Tracker_Semantic_Status $semantic, array $column_ids)
    {
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
