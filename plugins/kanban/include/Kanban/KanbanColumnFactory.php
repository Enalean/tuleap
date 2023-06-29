<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Kanban;

use PFUser;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_Semantic_Status;
use TrackerFactory;

class KanbanColumnFactory
{
    public function __construct(
        private readonly KanbanColumnDao $column_dao,
        private readonly KanbanUserPreferences $user_preferences,
    ) {
    }

    /**
     *
     * @return KanbanColumn[]
     */
    public function getAllKanbanColumnsForAKanban(Kanban $kanban, PFUser $user): array
    {
        $columns  = [];
        $semantic = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            return $columns;
        }

        $field_values = $this->getFieldValues($semantic);
        $open_values  = $this->getOpenValues($semantic);

        foreach ($field_values as $field_value) {
            $id = (int) $field_value->getId();
            if (in_array($id, $open_values)) {
                $columns[] = $this->instantiate($kanban, $id, $user, $field_values);
            }
        }

        return $columns;
    }

    public function getColumnForAKanban(Kanban $kanban, int $column_id, PFUser $user): KanbanColumn
    {
        $semantic = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            throw new SemanticStatusNotFoundException();
        }

        $open_values = $this->getOpenValues($semantic);

        foreach ($open_values as $id) {
            if ($id === $column_id) {
                $field_values = $this->getFieldValues($semantic);

                return $this->instantiate($kanban, $id, $user, $field_values);
            }
        }

        throw new KanbanColumnNotFoundException($kanban, $column_id);
    }

    private function instantiate(Kanban $kanban, int $id, PFUser $user, array $field_values): KanbanColumn
    {
        return new KanbanColumn(
            $id,
            $kanban->getId(),
            $field_values[$id]->getLabel(),
            $this->user_preferences->isColumnOpen($kanban, $id, $user),
            $this->getWIPLimitForColumn($kanban, $id),
            $this->isColumnRemovable($kanban, $field_values[$id])
        );
    }

    private function getWIPLimitForColumn(Kanban $kanban, int $column_id): ?int
    {
        return $this->column_dao->getColumnWipLimit($kanban->getId(), $column_id);
    }

    private function isColumnRemovable(Kanban $kanban, Tracker_FormElement_Field_List_Bind_StaticValue $value): bool
    {
        $semantic = $this->getSemanticStatus($kanban);

        if (! $semantic) {
            return false;
        }

        $field = $semantic->getField();
        if (! $field) {
            return false;
        }

        return $field->getBind()->canValueBeHiddenWithoutCheckingSemanticStatus($value) && ! $semantic->isBasedOnASharedField();
    }

    private function getOpenValues(Tracker_Semantic_Status $semantic): array
    {
        return $semantic->getOpenValues();
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    private function getFieldValues(Tracker_Semantic_Status $semantic): array
    {
        $field = $semantic->getField();
        if (! $field) {
            return [];
        }
        return $field->getAllValues();
    }

    private function getSemanticStatus(Kanban $kanban): ?Tracker_Semantic_Status
    {
        $tracker = TrackerFactory::instance()->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            return null;
        }

        $semantic = Tracker_Semantic_Status::forceLoad($tracker);
        if (! $semantic->getFieldId()) {
            return null;
        }

        return $semantic;
    }
}
