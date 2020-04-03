<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use Tuleap\DB\DataAccessObject;

class FrozenFieldsDao extends DataAccessObject
{
    public function isFieldUsedInPostAction($field_id): bool
    {
        $sql = 'SELECT NULL
            FROM plugin_tracker_workflow_postactions_frozen_fields_value AS paf
            WHERE paf.field_id = ?';
        $result = $this->getDB()->cell($sql, $field_id);

        return $result !== false;
    }

    public function searchByTransitionId(int $transition_id): array
    {
        $sql = 'SELECT paf.*
            FROM plugin_tracker_workflow_postactions_frozen_fields_value AS paf
                 JOIN plugin_tracker_workflow_postactions_frozen_fields AS paro
            ON (paro.id = paf.postaction_id)
            WHERE paro.transition_id = ?';

        return $this->getDB()->q($sql, $transition_id);
    }

    public function isAFrozenFieldPostActionUsedInTracker(int $tracker_id): bool
    {
        $sql = 'SELECT NULL
            FROM tracker_workflow
            LEFT JOIN tracker_workflow_transition ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
            LEFT JOIN plugin_tracker_workflow_postactions_frozen_fields ON (tracker_workflow_transition.transition_id = plugin_tracker_workflow_postactions_frozen_fields.transition_id)
            WHERE tracker_workflow.tracker_id = ?
                AND plugin_tracker_workflow_postactions_frozen_fields.id IS NOT NULL;';

        $result = $this->getDB()->cell($sql, $tracker_id);

        return $result !== false;
    }

    public function createPostActionForTransitionId(int $transition_id, array $field_ids): void
    {
        $frozen_fields_action_id = (int) $this->getDB()->insertReturnId(
            "plugin_tracker_workflow_postactions_frozen_fields",
            ["transition_id" => $transition_id]
        );

        foreach ($field_ids as $field_id) {
            $this->getDB()->insert(
                "plugin_tracker_workflow_postactions_frozen_fields_value",
                [
                    "postaction_id" => $frozen_fields_action_id,
                    "field_id"      => $field_id,
                ]
            );
        }
    }

    public function deletePostActionsByTransitionId(int $transition_id): void
    {
        $sql = "
            DELETE plugin_tracker_workflow_postactions_frozen_fields, plugin_tracker_workflow_postactions_frozen_fields_value
            FROM plugin_tracker_workflow_postactions_frozen_fields
            LEFT JOIN plugin_tracker_workflow_postactions_frozen_fields_value
                ON plugin_tracker_workflow_postactions_frozen_fields_value.postaction_id = plugin_tracker_workflow_postactions_frozen_fields.id
            WHERE plugin_tracker_workflow_postactions_frozen_fields.transition_id = ?";

        $this->getDB()->run(
            $sql,
            $transition_id
        );
    }

    public function deleteAllPostActionsForWorkflow(int $workflow_id): void
    {
        $sql = "
            DELETE plugin_tracker_workflow_postactions_frozen_fields, plugin_tracker_workflow_postactions_frozen_fields_value
            FROM tracker_workflow
                INNER JOIN tracker_workflow_transition
                    ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                INNER JOIN plugin_tracker_workflow_postactions_frozen_fields
                    ON (tracker_workflow_transition.transition_id = plugin_tracker_workflow_postactions_frozen_fields.transition_id)
                LEFT JOIN plugin_tracker_workflow_postactions_frozen_fields_value
                    ON plugin_tracker_workflow_postactions_frozen_fields_value.postaction_id = plugin_tracker_workflow_postactions_frozen_fields.id
            WHERE tracker_workflow.workflow_id = ?";

        $this->getDB()->run(
            $sql,
            $workflow_id
        );
    }
}
