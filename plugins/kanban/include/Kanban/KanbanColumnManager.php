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

declare(strict_types=1);

namespace Tuleap\Kanban;

use PFUser;
use Tracker_Semantic_Status;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;

class KanbanColumnManager
{
    public function __construct(
        private readonly KanbanColumnDao $column_dao,
        private readonly BindStaticValueDao $formelement_field_list_bind_static_value_dao,
        private readonly KanbanActionsChecker $kanban_actions_checker,
    ) {
    }

    /**
     * @throws SemanticStatusNotDefinedException
     */
    public function createColumn(PFUser $user, Kanban $kanban, string $label): ?int
    {
        $this->kanban_actions_checker->checkUserCanAddColumns($user, $kanban);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        return $semantic->addOpenValue($label);
    }

    public function reorderColumns(PFUser $user, Kanban $kanban, array $column_ids): void
    {
        $this->kanban_actions_checker->checkUserCanReorderColumns($user, $kanban);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        $this->checkAllColumnsAreProvided($semantic, $column_ids);

        $semantic->getField()?->getBind()->getValueDao()->reorder($column_ids);
    }

    /**
     * @throws KanbanColumnNotRemovableException
     * @throws KanbanSemanticStatusBasedOnASharedFieldException
     * @throws KanbanSemanticStatusNotBoundToStaticValuesException
     * @throws KanbanSemanticStatusNotDefinedException
     * @throws KanbanTrackerNotDefinedException
     */
    public function deleteColumn(PFUser $user, Kanban $kanban, KanbanColumn $column): void
    {
        $this->kanban_actions_checker->checkUserCanDeleteColumn($user, $kanban, $column);

        $tracker  = $this->kanban_actions_checker->getTrackerForKanban($kanban);
        $semantic = $this->kanban_actions_checker->getSemanticStatus($tracker);

        $this->column_dao->deleteColumn(
            $column->getKanbanId(),
            $column->getId(),
            function () use ($semantic, $column) {
                return $semantic->removeOpenValue($column->getId())
                    && $this->hideColumnFromTrackerFieldStaticValues($column);
            },
        );
    }

    public function updateWipLimit(
        PFUser $user,
        Kanban $kanban,
        KanbanColumn $column,
        int $wip_limit,
    ): void {
        $this->kanban_actions_checker->checkUserCanAdministrate($user, $kanban);

        $this->column_dao->setColumnWipLimit($column->getKanbanId(), $column->getId(), $wip_limit);
    }

    public function updateLabel(PFUser $user, Kanban $kanban, KanbanColumn $column, string $label): bool
    {
        $this->kanban_actions_checker->checkUserCanAdministrate($user, $kanban);
        $this->kanban_actions_checker->checkUserCanEditColumnLabel($user, $kanban);

        return $this->formelement_field_list_bind_static_value_dao->updateLabel($column->getId(), $label);
    }

    private function hideColumnFromTrackerFieldStaticValues(KanbanColumn $column): bool
    {
        return $this->formelement_field_list_bind_static_value_dao->hideValue($column->getId());
    }

    private function checkAllColumnsAreProvided(Tracker_Semantic_Status $semantic, array $column_ids): void
    {
        $all_open_values     = $semantic->getOpenValues();
        $values_not_provided = array_diff($all_open_values, $column_ids);
        $values_not_open     = array_diff($column_ids, $all_open_values);

        if (! empty($values_not_provided)) {
            throw new KanbanSemanticStatusAllColumnIdsNotProvidedException();
        }

        if (! empty($values_not_open)) {
            throw new KanbanSemanticStatusColumnIdsNotInOpenSemanticException();
        }
    }
}
