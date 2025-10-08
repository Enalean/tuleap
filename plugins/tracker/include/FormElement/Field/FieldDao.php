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

namespace Tuleap\Tracker\FormElement\Field;

use DataAccessObject;

class FieldDao extends DataAccessObject
{
    public function searchByTrackerIdAndName($tracker_id, $name)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $name       = $this->da->quoteSmart($name);
        $sql        = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                  AND name = $name";
        return $this->retrieve($sql);
    }

    public function searchUsedByTrackerIdAndName($tracker_id, $name)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $name       = $this->da->quoteSmart($name);
        $sql        = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                  AND name = $name
                  AND use_it = 1";
        return $this->retrieve($sql);
    }

    public function searchUnusedByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                  AND use_it = 0
                ORDER BY parent_id, `rank`";
        return $this->retrieve($sql);
    }

    public function searchUsedByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                  AND parent_id = 0
                  AND use_it = 1
                ORDER BY `rank`";
        return $this->retrieve($sql);
    }

    public function searchUsedByIdAndType($tracker_id, $field_id, $type)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);
        if (is_array($type)) {
            $type_stm = ' IN (' . implode(',', array_map([$this->da, 'quoteSmart'], $type)) . ') ';
        } else {
            $type     = $this->da->quoteSmart($type);
            $type_stm = " = $type";
        }
        $sql = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                  AND id = $field_id
                  AND use_it = 1
                  AND formElement_type $type_stm
                ORDER BY `rank`";
        return $this->retrieve($sql);
    }

    public function searchUsedUserClosedListFieldsByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT *
                FROM tracker_field AS f, tracker_field_list_bind_users AS lbu
                WHERE f.tracker_id = $tracker_id
                  AND use_it = 1
                  AND f.id = lbu.field_id
                  AND formElement_type IN ('sb', 'msb', 'cb', 'rb')
                ORDER BY `rank`";

        return $this->retrieve($sql);
    }

    public function getUsedUserClosedListFieldById($tracker_id, $field_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);
        $sql        = "SELECT *
                FROM tracker_field f, tracker_field_list_bind_users lbu
                WHERE f.tracker_id = $tracker_id
                  AND f.id = $field_id
                  AND use_it = 1
                  AND f.id = lbu.field_id
                  AND formElement_type IN ('sb', 'msb', 'cb', 'rb')
                ORDER BY `rank`";
        return $this->retrieve($sql);
    }

    public function searchUsedStaticSbFieldByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT f.*
                FROM tracker_field f
                  INNER JOIN tracker_field_list fl ON (fl.field_id = f.id AND fl.bind_type = 'static')
                WHERE f.tracker_id = $tracker_id
                  AND use_it = 1
                  AND formElement_type IN ('sb', 'msb')
                ORDER BY `rank`";
        return $this->retrieve($sql);
    }

    public function searchByParentId($parent_id)
    {
        $parent_id = $this->da->escapeInt($parent_id);
        $sql       = "SELECT *
                FROM tracker_field
                WHERE parent_id = $parent_id
                ORDER BY `rank`";
        return $this->retrieve($sql);
    }

    public function searchUsedByParentId($parent_id)
    {
        $parent_id = $this->da->escapeInt($parent_id);
        $sql       = "SELECT *
                FROM tracker_field
                WHERE parent_id = $parent_id
                  AND use_it = 1
                ORDER BY `rank`";
        return $this->retrieve($sql);
    }

    public function searchUsedByTrackerIdAndType($tracker_id, $type, $used = null)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        if (is_array($type)) {
            $type_stm = ' IN (' . implode(',', array_map([$this->da, 'quoteSmart'], $type)) . ') ';
        } else {
            $type     = $this->da->quoteSmart($type);
            $type_stm = " = $type";
        }

        $sql = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                  AND formElement_type $type_stm";
        if ($used) {
            $sql .= ' AND use_it = 1';
        }
        $sql .= ' ORDER BY `rank`';
        return $this->retrieve($sql);
    }

    public function searchById($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT * FROM tracker_field WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function searchNextUsedSibling($tracker_id, $id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $id         = $this->da->escapeInt($id);
        $sql        = "SELECT R2.*
                FROM tracker_field AS R1 INNER JOIN
                     tracker_field AS R2 ON (R1.tracker_id = R2.tracker_id AND R1.parent_id = R2.parent_id AND R2.`rank` > R1.`rank`)
                WHERE R1.id = $id
                  AND R2.use_it = 1
                ORDER BY R2.`rank`
                LIMIT 1";
        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT *
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                ORDER BY parent_id, `rank`";
        return $this->retrieve($sql);
    }

    public function duplicate($from_field_id, $to_tracker_id)
    {
        //TODO: duplicate tracker_id
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_tracker_id = $this->da->escapeInt($to_tracker_id);
        $sql           = "INSERT INTO tracker_field (tracker_id, parent_id, name, formElement_type, label, description, scope, required, use_it, `rank`, notifications, original_field_id)
                SELECT $to_tracker_id, parent_id, name, formElement_type, label, description, scope, required, use_it, `rank`, notifications, original_field_id
                FROM tracker_field
                WHERE id = $from_field_id";
        return $this->updateAndGetLastId($sql);
    }

    public function mapNewParentsAfterDuplication($tracker_id, $mapping)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $cases      = '';
        foreach ($mapping as $map) {
            $cases .= ' WHEN ' . $map['from'] . ' THEN ' . $map['to'] . PHP_EOL;
        }
        if ($cases) {
            $sql = "UPDATE tracker_field
                    SET parent_id = CASE parent_id
                                    $cases
                                    END
                    WHERE tracker_id = $tracker_id;";
            return $this->update($sql);
        }
        return true;
    }

    public function save($field)
    {
        $rank = (int) $this->prepareRanking(
            'tracker_field',
            $field->id,
            $field->parent_id,
            $field->rank,
            'id',
            'parent_id',
            'rank',
            'tracker_id',
            (int) $field->tracker_id
        );

        $sql = 'UPDATE tracker_field
                SET parent_id         = ' . $this->da->escapeInt($field->parent_id) . ',
                    label             = ' . $this->da->quoteSmart($field->label) . ',
                    name              = ' . $this->da->quoteSmart($field->name) . ',
                    description       = ' . $this->da->quoteSmart($field->description) . ',
                    scope             = ' . $this->da->quoteSmart($field->scope) . ',
                    required          = ' . $this->da->escapeInt($field->required ? 1 : 0) . ',
                    notifications     = ' . ($field->notifications ? 1 : 'NULL') . ',
                    use_it            = ' . $this->da->escapeInt($field->use_it ? 1 : 0) . ',
                    `rank`            = ' . $this->da->escapeInt($rank) . ',
                    original_field_id = ' . $this->da->escapeInt($field->getOriginalFieldId()) . '
                WHERE id = ' . $this->da->escapeInt($field->id);
        if ($this->update($sql)) {
            $field->rank = $rank;
            return true;
        }
        return false;
    }

    public function setType($field, $type)
    {
        $sql = 'UPDATE tracker_field
                SET formElement_type = ' . $this->da->quoteSmart($type) . '
                WHERE id = ' . $this->da->escapeInt($field->id);
        if ($this->update($sql)) {
            return true;
        }
        return false;
    }

    public function delete($field)
    {
        $sql = 'DELETE FROM tracker_field
                WHERE id = ' . $this->da->escapeInt($field->id);
        return $this->update($sql);
    }

    public function searchSharedTargets($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT * FROM tracker_field WHERE original_field_id = $id";
        return $this->retrieve($sql);
    }

    public function doesTrackerHaveSourceSharedFields(int $tracker_id): bool
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT NULL
                FROM tracker_field AS target_field
                    INNER JOIN tracker_field AS original_field ON (target_field.original_field_id = original_field.id)
                    INNER JOIN tracker ON (tracker.id = original_field.tracker_id)
                WHERE tracker.id = $tracker_id
                    AND target_field.original_field_id > 0";

        $result = $this->retrieve($sql);
        return ($result instanceof \Countable) && count($result) > 0;
    }

    /**
     * Returns all the original shared fields of a project
     *
     * Given:
     * Project A
     * |-- Tracker Release
     *     |-- Field #334 Customer <-------------------------|
     *                                                       |
     * Project B (id: 104)                                   |
     * |-- Tracker Release                                   |
     *     |-- Field #543 Customer (original_field_id = 334)-'
     *     |-- Field #600 Confidentiality <-------------------------|
     * |-- Tracker Sprint                                           |
     *     |-- Field #650 Confidnetiality (original_field_id = 600)-'
     *
     * This method returns rows of field 334 and 600
     *
     * @param int $project_id
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function searchProjectSharedFieldsOriginals($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $sql        = "SELECT original_field.*
                FROM tracker_field AS original_field
                    INNER JOIN tracker_field AS used_field ON (original_field.id = used_field.original_field_id)
                    INNER JOIN tracker ON (tracker.id = used_field.tracker_id)
                WHERE tracker.group_id = $project_id
                    AND used_field.original_field_id != 0
                    AND used_field.use_it = 1
                    AND tracker.deletion_date IS NULL
                GROUP BY original_field.id";
        return $this->retrieve($sql);
    }

    public function searchProjectSharedFieldsTargets($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $sql        = "SELECT tracker_field.*
                FROM tracker_field
                INNER JOIN tracker ON tracker.id = tracker_field.tracker_id
                WHERE tracker.group_id = $project_id
                  AND tracker_field.original_field_id != 0";
        return $this->retrieve($sql);
    }

    public function searchFieldIdsByGroupId($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "
            SELECT f.*

            FROM       tracker_field AS f
            INNER JOIN tracker       AS t ON (f.tracker_id = t.id)

            WHERE t.group_id = $group_id
            AND   f.use_it   = 1
            AND   t.deletion_date IS NULL
        ";

        return $this->retrieveIds($sql);
    }

    public function create(
        $type,
        $tracker_id,
        $parent_id,
        $name,
        $prefix_name,
        $label,
        $description,
        $use_it,
        $scope,
        $required,
        $notifications,
        $rank,
        $original_field_id,
        $force_absolute_ranking,
    ) {
        $type          = $this->da->quoteSmart($type);
        $tracker_id    = $this->da->escapeInt($tracker_id);
        $parent_id     = $this->da->escapeInt($parent_id);
        $name_like     = $this->da->quoteLikeValueSuffix($prefix_name);
        $prefix_name   = $this->da->quoteSmart($prefix_name);
        $label         = $this->da->quoteSmart($label);
        $description   = $this->da->quoteSmart($description);
        $use_it        = $this->da->escapeInt($use_it);
        $scope         = $this->da->quoteSmart($scope);
        $required      = $this->da->escapeInt($required);
        $notifications = ($notifications ? 1 : 'NULL');
        if ($force_absolute_ranking) {
            $rank = (int) $rank;
        } else {
            $rank = (int) $this->prepareRanking(
                'tracker_field',
                0,
                (int) $parent_id,
                $rank,
                'id',
                'parent_id',
                'rank',
                'tracker_id',
                (int) $tracker_id
            );
        }
        $original_field_id = $this->da->escapeInt($original_field_id);

        $sql = 'INSERT INTO tracker_field (tracker_id, parent_id, name, formElement_type, label, description, scope, required, use_it, `rank`, notifications, original_field_id) ';
        if ($name) {
            $name = $this->da->quoteSmart($name);
            $sql .= "
                VALUES ($tracker_id, $parent_id, $name, $type, $label, $description, $scope, $required, $use_it, $rank, $notifications, $original_field_id)";
        } else {
            $sql .= "
                SELECT $tracker_id, $parent_id, CONCAT($prefix_name, IFNULL(MAX(REPLACE(name, $prefix_name, '')), 0) + 1), $type, $label, $description, $scope, $required, $use_it, $rank, $notifications, $original_field_id
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                    AND name LIKE $name_like";
        }
        return $this->updateAndGetLastId($sql);
    }

    public function updateOriginalFieldId($id, $original_field_id)
    {
        $original_field_id = $this->da->escapeInt($original_field_id);
        $id                = $this->da->escapeInt($id);

        $sql = "
            UPDATE tracker_field
            SET   original_field_id = $original_field_id
            WHERE id                = $id
        ";

        return $this->update($sql);
    }
}
