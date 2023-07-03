<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use DataAccessObject;
use Tracker_FormElement_Field;

class BindStaticValueDao extends DataAccessObject
{
    public const COPY_BY_REFERENCE = true;
    public const COPY_BY_VALUE     = false;

    private array $cache_used_values_in_artifacts = [];
    private array $cache_cannot_be_hidden_values  = [];

    public function searchById($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM tracker_field_list_bind_static_value
                WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function searchByFieldId($field_id, $is_rank_alpha)
    {
        $field_id = $this->da->escapeInt($field_id);
        $sql      = "SELECT *
                FROM tracker_field_list_bind_static_value
                WHERE field_id = $field_id
                ORDER BY " . ($is_rank_alpha ? 'label' : '`rank`');
        return $this->retrieve($sql);
    }

    public function duplicate($from_value_id, $to_field_id, $by_reference)
    {
        $from_value_id = $this->da->escapeInt($from_value_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);
        if ($by_reference) {
            $insert = "INSERT INTO tracker_field_list_bind_static_value (field_id, label, description, `rank`, is_hidden, original_value_id)
                    SELECT $to_field_id, label, description, `rank`, is_hidden, $from_value_id";
        } else {
            $insert = "INSERT INTO tracker_field_list_bind_static_value (field_id, label, description, `rank`, is_hidden, original_value_id)
                    SELECT $to_field_id, label, description, `rank`, is_hidden, original_value_id";
        }
        $sql = $insert . "
                FROM tracker_field_list_bind_static_value
                WHERE id = $from_value_id";

        return $this->updateAndGetLastId($sql);
    }

    public function create($field_id, $label, $description, $rank, $is_hidden)
    {
        $field_id    = $this->da->escapeInt($field_id);
        $label       = $this->da->quoteSmart($label);
        $description = $this->da->quoteSmart($description);
        $rank        = $this->da->escapeInt(
            $this->prepareRanking('tracker_field_list_bind_static_value', 0, (int) $field_id, $rank, 'id', 'field_id')
        );
        $is_hidden   = $this->da->escapeInt($is_hidden);

        $sql = "INSERT INTO tracker_field_list_bind_static_value (field_id, label, description, `rank`, is_hidden)
                VALUES ($field_id, $label, $description, $rank, $is_hidden)";
        return $this->updateAndGetLastId($sql);
    }

    public function propagateCreation($field, $original_value_id)
    {
        $field_id          = $this->da->escapeInt($field->id);
        $original_value_id = $this->da->escapeInt($original_value_id);

        $sql = "INSERT INTO tracker_field_list_bind_static_value (field_id, label, description, `rank`, is_hidden, original_value_id)
                SELECT target.id, original_value.label, original_value.description, original_value.`rank`, original_value.is_hidden, $original_value_id
                    FROM tracker_field_list_bind_static_value AS original_value
                    INNER JOIN tracker_field AS target ON (target.original_field_id = original_value.field_id)
                    WHERE original_value.field_id = $field_id
                        AND original_value.id = $original_value_id
                        AND original_value.field_id != target.id";
        return $this->retrieve($sql);
    }

    public function hideValue($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "UPDATE tracker_field_list_bind_static_value
                SET is_hidden = 1
                WHERE id = $id
                   OR original_value_id = $id";

        return $this->update($sql);
    }

    public function updateLabel($id, $label)
    {
        $id    = $this->da->escapeInt($id);
        $label = $this->da->quoteSmart($label);

        $sql = "UPDATE tracker_field_list_bind_static_value
                SET label = $label
                WHERE id = $id
                   OR original_value_id = $id";

        return $this->update($sql);
    }

    public function save($id, $field_id, $label, $description, $rank, $is_hidden)
    {
        $id          = $this->da->escapeInt($id);
        $field_id    = $this->da->escapeInt($field_id);
        $label       = $this->da->quoteSmart($label);
        $description = $this->da->quoteSmart($description);
        $rank        = $this->da->escapeInt(
            $this->prepareRanking('tracker_field_list_bind_static_value', (int) $id, (int) $field_id, $rank, 'id', 'field_id')
        );
        $is_hidden   = $this->da->escapeInt($is_hidden);

        $sql = "UPDATE tracker_field_list_bind_static_value
                SET label = $label,
                    description = $description,
                    `rank` = $rank,
                    is_hidden = $is_hidden
                WHERE id = $id
                  OR original_value_id = $id";
        return $this->update($sql);
    }

    public function delete(Tracker_FormElement_Field $field, int $id): bool
    {
        if (! $this->canValueBeDeleted($field, $id)) {
            return false;
        }

        $id  = $this->da->escapeInt($id);
        $sql = "DELETE FROM tracker_field_list_bind_static_value
                WHERE id = $id
                   OR original_value_id = $id";

        return $this->update($sql);
    }

    public function searchChangesetValues($changeset_id, $field_id, $is_rank_alpha)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql          = "SELECT f.id
                FROM tracker_field_list_bind_static_value AS f
                     INNER JOIN tracker_changeset_value_list AS l ON (l.bindvalue_id = f.id)
                     INNER JOIN tracker_changeset_value AS c
                     ON ( l.changeset_value_id = c.id
                      AND c.changeset_id = $changeset_id
                      AND c.field_id = $field_id
                     )
                ORDER BY f." . ($is_rank_alpha ? 'label' : '`rank`');
        return $this->retrieve($sql);
    }

    public function canValueBeHiddenWithoutCheckingSemanticStatus(Tracker_FormElement_Field $field, $value_id)
    {
        return $this->isValueHiddenable($field->getId(), $value_id, "");
    }

    public function canValueBeHidden(Tracker_FormElement_Field $field, $value_id): bool
    {
        $field_id   = $this->da->escapeInt($field->getId());
        $tracker_id = $this->da->escapeInt($field->getTracker()->getId());

        $semantic_status_statement = "
            UNION SELECT IF(v.original_value_id, v.original_value_id, v.id) AS id
            FROM tracker_field_list_bind_static_value AS v
                INNER JOIN tracker_semantic_status AS s
                ON ((s.open_value_id = v.id OR s.open_value_id = v.original_value_id)
                    AND s.field_id = v.field_id)
            WHERE v.field_id = $field_id";

        $semantic_done_statement = "
            UNION SELECT IF(static_value.original_value_id, static_value.original_value_id, static_value.id) AS id
                FROM tracker_field_list_bind_static_value AS static_value
                    INNER JOIN plugin_tracker_semantic_done AS semantic_done
                    ON (semantic_done.value_id = static_value.id OR semantic_done.value_id = static_value.original_value_id)
                WHERE semantic_done.tracker_id = $tracker_id
                AND static_value.field_id = $field_id";

        return $this->isValueHiddenable(
            $field_id,
            $value_id,
            $semantic_status_statement . $semantic_done_statement
        );
    }

    /**
     * A value can be hidden if:
     * - it's not used in workflow transition
     * - not used in triggers
     * - not used in field dependencies
     *
     * (in addition of other checks provided by $additional_unions like semantic checks)
     */
    private function isValueHiddenable($field_id, $value_id, string $additional_unions): bool
    {
        $field_id = $this->da->escapeInt($field_id);

        if (! isset($this->cache_cannot_be_hidden_values[$field_id][$value_id])) {
            $value_id = $this->da->escapeInt($value_id);
            $sql      = "SELECT IF(v.original_value_id, v.original_value_id, v.id) AS id
                    FROM tracker_field_list_bind_static_value AS v
                        INNER JOIN tracker_workflow AS w ON (
                            v.field_id = w.field_id
                        )
                        INNER JOIN tracker_workflow_transition AS wt ON (
                            w.workflow_id = wt.workflow_id
                            AND
                            (wt.from_id = v.id OR (v.original_value_id <> 0 AND wt.from_id = v.original_value_id))
                        )
                    WHERE v.field_id = $field_id
                    UNION
                    SELECT IF(v.original_value_id, v.original_value_id, v.id) AS id
                    FROM tracker_field_list_bind_static_value AS v
                        INNER JOIN tracker_workflow AS w ON (
                            v.field_id = w.field_id
                        )
                        INNER JOIN tracker_workflow_transition AS wt ON (
                            w.workflow_id = wt.workflow_id
                            AND
                            (wt.to_id = v.id OR wt.to_id = v.original_value_id)
                        )
                    WHERE v.field_id = $field_id
                    UNION
                    SELECT v.id AS id
                    FROM tracker_field_list_bind_static_value AS v
                        INNER JOIN tracker_rule_list AS tr
                        ON (tr.source_field_id = v.field_id AND tr.source_value_id = v.id)
                    WHERE source_field_id = $field_id
                    UNION
                    SELECT v.id AS id
                    FROM tracker_field_list_bind_static_value AS v
                        INNER JOIN tracker_rule_list AS tr
                        ON (tr.target_field_id = v.field_id AND tr.target_value_id = v.id)
                    WHERE target_field_id = $field_id
                    UNION
                    SELECT copied_value.original_value_id AS id
                    FROM tracker_field AS original
                        INNER JOIN tracker_field AS copied_field ON(original.id = copied_field.original_field_id)
                        INNER JOIN tracker_field_list_bind_static_value AS copied_value ON (copied_value.field_id = copied_field.id)
                        INNER JOIN tracker_rule_list AS tr ON (
                            tr.source_field_id = copied_field.id AND tr.source_value_id = copied_value.id
                        )
                    WHERE original.id = $field_id
                    UNION
                    SELECT copied_value.original_value_id AS id
                    FROM tracker_field AS original
                        INNER JOIN tracker_field AS copied_field ON(original.id = copied_field.original_field_id)
                        INNER JOIN tracker_field_list_bind_static_value AS copied_value ON (copied_value.field_id = copied_field.id)
                        INNER JOIN tracker_rule_list AS tr ON (
                            tr.target_field_id = copied_field.id AND tr.target_value_id = copied_value.id
                        )
                    WHERE original.id = $field_id
                    UNION SELECT rule_static.value_id  AS id
                        FROM tracker_workflow_trigger_rule_static_value AS rule_static
                        WHERE  rule_static.value_id = $value_id
                    UNION SELECT rule_trg_static.value_id  AS id
                        FROM tracker_workflow_trigger_rule_trg_field_static_value AS rule_trg_static
                        WHERE  rule_trg_static.value_id = $value_id
                    $additional_unions
                    ";

            $this->cache_cannot_be_hidden_values[$field_id] = [];
            foreach ($this->retrieve($sql) as $row) {
                $this->cache_cannot_be_hidden_values[$field_id][$row['id']] = true;
            }
        }

        return ! isset($this->cache_cannot_be_hidden_values[$field_id][$value_id]);
    }

    /**
     * A value can be deleted if:
     * - it can be hidden
     * - not used in changeset history of not deleted artifacts
     */
    public function canValueBeDeleted(Tracker_FormElement_Field $field, $value_id): bool
    {
        $field_id = $this->da->escapeInt($field->getId());

        if (! isset($this->cache_used_values_in_artifacts[$field_id])) {
            $sql = "SELECT DISTINCT IF (v.original_value_id, v.original_value_id, v.id) AS id
                    FROM tracker_field_list_bind_static_value AS v
                        INNER JOIN tracker_changeset_value_list AS cvl ON (v.id = cvl.bindvalue_id)
                        INNER JOIN tracker_changeset_value AS cv ON (cv.id = cvl.changeset_value_id AND cv.field_id = v.field_id)
                        INNER JOIN tracker_changeset ON (tracker_changeset.id = cv.changeset_id)
                        INNER JOIN tracker_artifact ON (tracker_changeset.artifact_id = tracker_artifact.id)
                    WHERE v.field_id = $field_id
                    UNION
                    SELECT v.id AS id
                    FROM tracker_field_list_bind_static_value AS v
                        INNER JOIN tracker_changeset_value_openlist AS cvl ON (v.id = cvl.bindvalue_id)
                        INNER JOIN tracker_changeset_value AS cv ON (
                            cv.id = cvl.changeset_value_id
                            AND cv.field_id = v.field_id
                        )
                        INNER JOIN tracker_changeset ON (tracker_changeset.id = cv.changeset_id)
                        INNER JOIN tracker_artifact ON (tracker_changeset.artifact_id = tracker_artifact.id)
                    WHERE cv.field_id = $field_id
                    ";

            $this->cache_used_values_in_artifacts[$field_id] = [];
            foreach ($this->retrieve($sql) as $row) {
                $this->cache_used_values_in_artifacts[$field_id][$row['id']] = true;
            }
        }

        return $this->canValueBeHidden($field, $value_id) && ! isset($this->cache_used_values_in_artifacts[$field_id][$value_id]);
    }

    public function updateOriginalValueId($field_id, $old_original_value_id, $new_original_value_id)
    {
        $field_id              = $this->da->escapeInt($field_id);
        $old_original_value_id = $this->da->escapeInt($old_original_value_id);
        $new_original_value_id = $this->da->escapeInt($new_original_value_id);

        $sql = "
            UPDATE tracker_field_list_bind_static_value
            SET   original_value_id = $new_original_value_id
            WHERE field_id          = $field_id
            AND   original_value_id = $old_original_value_id
        ";

        return $this->update($sql);
    }

    public function reorder($ids_in_right_order)
    {
        $this->startTransaction();

        $ids_in_right_order = array_filter($ids_in_right_order);
        $ids                = $this->da->escapeIntImplode($ids_in_right_order);

        $when_conditions = '';
        foreach ($ids_in_right_order as $rank => $id) {
            $when_conditions .= " WHEN $id THEN $rank ";
        }

        $sql = "UPDATE tracker_field_list_bind_static_value
                SET `rank` = CASE id $when_conditions END
                WHERE id IN ($ids)";

        if (! $this->update($sql) || ! $this->reorderChildren($ids)) {
            $this->rollBack();
            return false;
        }

        return $this->commit();
    }

    private function reorderChildren($ids)
    {
        $sql = "UPDATE tracker_field_list_bind_static_value AS children_values, tracker_field_list_bind_static_value AS parent_values
                SET children_values.`rank` = parent_values.`rank`
                WHERE parent_values.id IN ($ids)
                    AND children_values.original_value_id = parent_values.id";

        return $this->update($sql);
    }

    public function searchValueByLabel($field_id, $label)
    {
        $field_id = $this->da->escapeInt($field_id);
        $label    = $this->da->quoteSmart($label);

        $sql = "SELECT *
                FROM tracker_field_list_bind_static_value
                WHERE field_id = $field_id AND label = $label";

        return $this->retrieve($sql);
    }
}
