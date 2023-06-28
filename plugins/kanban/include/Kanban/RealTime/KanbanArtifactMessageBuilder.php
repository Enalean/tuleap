<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\RealTime;

use Tuleap\Kanban\KanbanItemDao;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageException;

final class KanbanArtifactMessageBuilder
{
    public function __construct(
        private readonly KanbanItemDao $kanban_item_dao,
        private readonly Tracker_Artifact_ChangesetFactory $changeset_factory,
    ) {
    }

    public function buildArtifactUpdated(Artifact $artifact): KanbanArtifactUpdatedMessageRepresentation
    {
        return new KanbanArtifactUpdatedMessageRepresentation(
            $artifact->getId()
        );
    }

    /**
     * @throws RealTimeArtifactMessageException
     */
    public function buildArtifactMoved(Artifact $artifact): ?KanbanArtifactMovedMessageRepresentation
    {
        $tracker_semantic = Tracker_Semantic_Status::load($artifact->getTracker());
        $status_field     = $tracker_semantic->getField();

        if ($status_field === null) {
            return null;
        }

        $last_changeset = $this->changeset_factory->getLastChangeset($artifact);
        if (! $last_changeset) {
            throw new RealTimeArtifactMessageException(
                'Last changeset not found for the artifact id ' . $artifact->getId() . '.'
            );
        }

        $has_changed = $last_changeset->hasChanges([$status_field->getId() => $artifact->getStatusForChangeset($last_changeset)]);
        if (! $has_changed) {
            throw new RealTimeArtifactMessageException(
                'Field ' . $status_field->getLabel() . ' of the last changeset has not changed for the artifact id ' . $artifact->getId() . '.'
            );
        }

        $previous_changeset = $this->changeset_factory->getPreviousChangesetWithFieldValue($artifact, $status_field, $last_changeset->getId());
        if (! $previous_changeset) {
            throw new RealTimeArtifactMessageException(
                'Previous changeset not found for the artifact id ' . $artifact->getId() . '.'
            );
        }

        $values          = $status_field->getAllValues();
        $current_status  = $artifact->getStatusForChangeset($last_changeset);
        $previous_status = $artifact->getStatusForChangeset($previous_changeset);

        $is_none_current_status  = $status_field->isNone($current_status);
        $is_none_previous_status = $status_field->isNone($previous_status);

        $is_open_current_status  = $tracker_semantic->isOpenValue($current_status);
        $is_open_previous_status = $tracker_semantic->isOpenValue($previous_status);

        $in_column   = $this->getKanbanColumn($values, $current_status, $is_none_current_status, $is_open_current_status);
        $from_column = $this->getKanbanColumn($values, $previous_status, $is_none_previous_status, $is_open_previous_status);

        return new KanbanArtifactMovedMessageRepresentation(
            $this->getItemsIdsInColumn($artifact, $values, $in_column, $current_status),
            $artifact->getId(),
            $in_column,
            $from_column
        );
    }

    /**
     * @throws RealTimeArtifactMessageException
     */
    public function buildArtifactReordered(Artifact $artifact): ?KanbanArtifactMovedMessageRepresentation
    {
        $tracker_semantic = Tracker_Semantic_Status::load($artifact->getTracker());
        $status_field     = $tracker_semantic->getField();

        if ($status_field === null) {
            return null;
        }

        $last_changeset = $this->changeset_factory->getLastChangeset($artifact);
        if (! $last_changeset) {
            throw new RealTimeArtifactMessageException(
                'Last changeset not found for the artifact id ' . $artifact->getId() . '.'
            );
        }

        $values                 = $status_field->getAllValues();
        $current_status         = $artifact->getStatusForChangeset($last_changeset);
        $is_none_current_status = $status_field->isNone($current_status);
        $is_open_current_status = $tracker_semantic->isOpenValue($current_status);
        $in_column              = $this->getKanbanColumn($values, $current_status, $is_none_current_status, $is_open_current_status);

        return new KanbanArtifactMovedMessageRepresentation(
            $this->getItemsIdsInColumn($artifact, $values, $in_column, $current_status),
            $artifact->getId(),
            $in_column,
            $in_column
        );
    }

    private function getKanbanColumn(array $values, ?string $status, bool $is_none_status, bool $is_open_status): string|int
    {
        if ($is_none_status) {
            $column = ColumnIdentifier::BACKLOG_COLUMN;
        } elseif (! $is_open_status) {
            $column = ColumnIdentifier::ARCHIVE_COLUMN;
        } else {
            $column = $this->getColumnId($values, $status);
        }

        return $column;
    }

    private function getColumnId(array $values, ?string $status): int
    {
        $column_id = 0;

        foreach ($values as $value) {
            if ($value->getLabel() === $status) {
                $column_id = intval($value->getId());
            }
        }

        return $column_id;
    }

    private function getItemsIdsInColumn(Artifact $artifact, array $values, string|int $in_column, ?string $status): array
    {
        $column_item_ids = [];

        if ($in_column === ColumnIdentifier::BACKLOG_COLUMN) {
            $items_in_column = $this->kanban_item_dao->getKanbanBacklogItemIds($artifact->getTracker()->getId());
        } elseif ($in_column === ColumnIdentifier::ARCHIVE_COLUMN) {
            $items_in_column = $this->kanban_item_dao->getKanbanArchiveItemIds($artifact->getTracker()->getId());
        } else {
            $items_in_column = $this->kanban_item_dao->getItemsInColumn($artifact->getTracker()->getId(), $this->getColumnId($values, $status));
        }

        foreach ($items_in_column as $item) {
            $column_item_ids[] = intval($item['id']);
        }

        return $column_item_ids;
    }
}
