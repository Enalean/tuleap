<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Data Access Object for Tracker_Rule
 */
class Tracker_RuleDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_rule_list';
    }
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT *
                FROM tracker_rule JOIN tracker_rule_list
                ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)";
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by TrackerId
    * @return DataAccessResult
    */
    public function searchByTrackerId($tracker_id)
    {
        $sql = sprintf(
            "SELECT id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id
                        FROM tracker_rule JOIN tracker_rule_list
                        ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                        WHERE tracker_rule.tracker_id = %s",
            $this->da->quoteSmart($tracker_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table tracker_rule and in tracker_rule_list
    * @return true or id(auto_increment) if there is no error
    */
    public function create($tracker_id, $source_field_id, $source_value_id, $target_field_id, $rule_type, $target_value_id)
    {
        $sql_insert_rule = sprintf(
            "INSERT INTO tracker_rule (tracker_id, rule_type)
                            VALUES (%s, %s)",
            $this->da->quoteSmart($tracker_id),
            $this->da->quoteSmart($rule_type)
        );

        $tracker_rule_id = $this->updateAndGetLastId($sql_insert_rule);

        $sql = sprintf(
            "INSERT INTO tracker_rule_list (tracker_rule_id, source_field_id, source_value_id, target_field_id, target_value_id)
                        VALUES (%s, %s, %s, %s, %s)",
            $tracker_rule_id,
            $this->da->quoteSmart($source_field_id),
            $this->da->quoteSmart($source_value_id),
            $this->da->quoteSmart($target_field_id),
            $this->da->quoteSmart($target_value_id)
        );
        $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by tracker_id
    * @return DataAccessResult
    */
    public function searchByTrackerIdWithOrder($tracker_id)
    {
       //$sql = sprintf("SELECT ar.id, ar.source_field_id, ar.source_value_id, ar.target_field_id, ar.rule_type, ar.target_value_id ".
       //               " FROM tracker_rule AS ar ".
       //               "   INNER JOIN tracker_field_usage AS afu1 ON (ar.source_field_id = afu1.field_id AND ar.group_artifact_id = afu1.group_artifact_id) ".
       //               "   INNER JOIN tracker_field_usage AS afu2 ON (ar.target_field_id = afu2.field_id AND ar.group_artifact_id = afu2.group_artifact_id) ".
       //               "   LEFT JOIN tracker_field_value_list AS afvls ".
       //               "      ON (ar.source_field_id = afvls.field_id AND ar.group_artifact_id = afvls.group_artifact_id AND ar.source_value_id = afvls.value_id) ".
       //               "   LEFT JOIN tracker_field_value_list AS afvlt ".
       //               "      ON (ar.target_field_id = afvlt.field_id AND ar.group_artifact_id = afvlt.group_artifact_id AND ar.target_value_id = afvlt.value_id) ".
       //               " WHERE ar.group_artifact_id = %s ".
       //               " ORDER BY afu1.place, afu2.place, afvls.order_id, afvlt.order_id, ar.id",
       //        $this->da->quoteSmart($tracker_id));
               $sql = sprintf(
                   "SELECT id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id " .
                              "FROM tracker_rule JOIN tracker_rule_list
                               ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)" .
                              " WHERE tracker_id = %s " .
                              " ORDER BY id",
                   $this->da->quoteSmart($tracker_id)
               );
        return $this->retrieve($sql);
    }

    public function deleteById($id)
    {
        $sql_delete_list = sprintf(
            "DELETE FROM tracker_rule_list WHERE tracker_rule_id = %s",
            $this->da->quoteSmart($id)
        );
        $this->update($sql_delete_list);

        $sql = sprintf(
            "DELETE FROM tracker_rule WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_source_id = $this->da->escapeInt($field_source_id);
        $field_target_id = $this->da->escapeInt($field_target_id);

        $this->startTransaction();
        try {
            $sql_delete_list = "DELETE tracker_rule.*
                                FROM tracker_rule JOIN tracker_rule_list
                                ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                                WHERE source_field_id   = $field_source_id
                                    AND target_field_id   = $field_target_id";

            $this->update($sql_delete_list);

            $sql = "DELETE
                    FROM $this->table_name
                    WHERE  source_field_id   = $field_source_id
                        AND target_field_id   = $field_target_id";
            $this->update($sql);
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
        $this->commit();
    }

    public function searchBySourceTarget($tracker_id, $field_source_id, $field_target_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_source_id = $this->da->escapeInt($field_source_id);
        $field_target_id = $this->da->escapeInt($field_target_id);
        $sql = "SELECT *
                FROM tracker_rule JOIN $this->table_name
                ON (tracker_rule.id = $this->table_name.tracker_rule_id)
                WHERE tracker_id = $tracker_id
                  AND source_field_id   = $field_source_id
                  AND target_field_id   = $field_target_id";
        return $this->retrieve($sql);
    }

    public function searchInvolvedFieldsByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT DISTINCT tracker_rule_list.source_field_id, tracker_rule_list.target_field_id
                FROM tracker_rule JOIN tracker_rule_list
                ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                WHERE tracker_rule.tracker_id = '$tracker_id'";
        return $this->retrieve($sql);
    }
}
