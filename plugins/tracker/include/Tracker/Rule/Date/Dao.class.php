<?php
/**
  * Copyright (c) Enalean, 2012. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

/**
 *  Data Access Object for Tracker_Rule
 */
class Tracker_Rule_Date_Dao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_rule_date';
    }

    /**
     * Searches Tracker_Rule by Id
     * @return DataAccessResult
     */
    public function searchById($tracker_id, $id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $id         = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM tracker_rule_date
                    JOIN tracker_rule
                    ON (id = tracker_rule_id)
                WHERE tracker_rule.id = $id
                  AND tracker_rule.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    /**
     * Searches Tracker_Rule by TrackerId
     * @return DataAccessResult
     */
    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM tracker_rule
                    JOIN tracker_rule_date
                    ON (id = tracker_rule_id)
                WHERE tracker_rule.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    /**
     *
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param string $comparator
     * @return int The ID of the saved tracker_rule
     * @throws Exception
     */
    public function insert($tracker_id, $source_field_id, $target_field_id, $comparator)
    {
        $tracker_id      = $this->da->escapeInt($tracker_id);
        $source_field_id = $this->da->escapeInt($source_field_id);
        $target_field_id = $this->da->escapeInt($target_field_id);
        $comparator      = $this->da->quoteSmart($comparator);
        $rule_type       = $this->da->escapeInt(Tracker_Rule::RULETYPE_DATE);

        $this->startTransaction();
        try {
            $sql = "INSERT INTO tracker_rule (tracker_id, rule_type)
                    VALUES ($tracker_id, $rule_type)";
            $id  = $this->updateAndGetLastId($sql);
            $sql = "INSERT INTO tracker_rule_date (tracker_rule_id, source_field_id, target_field_id, comparator)
                    VALUES ($id, $source_field_id, $target_field_id, $comparator)";
            $this->update($sql);
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
        $this->commit();

        return $id;
    }

    public function deleteById($tracker_id, $rule_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $rule_id    = $this->da->escapeInt($rule_id);
        $sql = "DELETE tracker_rule_date.*
                FROM tracker_rule
                    INNER JOIN tracker_rule_date ON (id = tracker_rule_id)
                WHERE id = $rule_id
                  AND tracker_id = $tracker_id;";
        if ($this->update($sql)) {
            $sql = "DELETE
                    FROM tracker_rule
                    WHERE id = $rule_id
                      AND tracker_id = $tracker_id";
            return $this->update($sql);
        }
    }

    public function save($id, $source_field_id, $target_field_id, $comparator)
    {
        $id              = $this->da->escapeInt($id);
        $source_field_id = $this->da->escapeInt($source_field_id);
        $target_field_id = $this->da->escapeInt($target_field_id);
        $comparator      = $this->da->quoteSmart($comparator);

        $sql = "UPDATE tracker_rule_date
                SET source_field_id = $source_field_id,
                    target_field_id = $target_field_id,
                    comparator      = $comparator
                WHERE tracker_rule_id = $id";

        return $this->update($sql);
    }
}
