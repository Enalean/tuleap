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
class Tracker_Rule_List_Dao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_rule_list';
    }

    /**
     * Searches Tracker_Rule by Id
     * @return DataAccessResult | false
     */
    public function searchById($id)
    {
        $rule_id = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM tracker_rule_list
                    JOIN tracker_rule
                    ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                WHERE tracker_rule.id = $rule_id";
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
                    JOIN tracker_rule_list
                    ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                WHERE tracker_rule.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    /**
     *
     * @return int The ID of the saved tracker_rule
     */
    public function insert(Tracker_Rule_List $rule)
    {
        $rule_id         = $this->da->escapeInt($rule->getTrackerId());
        $rule_type       = $this->da->quoteSmart(Tracker_Rule::RULETYPE_VALUE);

        $source_field_id = $this->da->escapeInt($rule->getSourceFieldId());
        if ($rule->getSourceValue() instanceof Tracker_FormElement_Field_List_Value) {
            $source_value_id = $this->da->quoteSmart($rule->getSourceValue()->getId());
        } else {
            $source_value_id = $this->da->quoteSmart($rule->getSourceValue());
        }

        $target_field_id = $this->da->escapeInt($rule->getTargetFieldId());
        if ($rule->getTargetValue() instanceof Tracker_FormElement_Field_List_Value) {
            $target_value_id = $this->da->quoteSmart($rule->getTargetValue()->getId());
        } else {
            $target_value_id = $this->da->quoteSmart($rule->getTargetValue());
        }

        $sql_insert_rule = "INSERT INTO tracker_rule (tracker_id, rule_type)
                                VALUES ($rule_id, $rule_type)";

        $this->startTransaction();

        try {
            $tracker_rule_id = $this->updateAndGetLastId($sql_insert_rule);

            $sql = "INSERT INTO tracker_rule_list (
                        tracker_rule_id, 
                        source_field_id, 
                        source_value_id, 
                        target_field_id, 
                        target_value_id
                        )
                    VALUES (
                        $tracker_rule_id, 
                        $source_field_id, 
                        $source_value_id, 
                        $target_field_id, 
                        $target_value_id)";
            $this->retrieve($sql);
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        $this->commit();

        return $tracker_rule_id;
    }

    /**
     * create a row in the table tracker_rule and in tracker_rule_list
     * @return true or id(auto_increment) if there is no error
     */
    public function create($tracker_id, $source_field_id, $source_value_id, $target_field_id, $target_value_id)
    {
        $rule_type       = Tracker_Rule::RULETYPE_VALUE;
        $tracker_id      = $this->da->escapeInt($tracker_id);
        $source_field_id = $this->da->escapeInt($source_field_id);
        $source_value_id = $this->da->escapeInt($source_value_id);
        $target_field_id = $this->da->escapeInt($target_field_id);
        $target_value_id = $this->da->escapeInt($target_value_id);

        $sql_insert_rule = "INSERT INTO tracker_rule (tracker_id, rule_type)
                            VALUES ($tracker_id, $rule_type)";

        try {
            $tracker_rule_id = $this->updateAndGetLastId($sql_insert_rule);

            $sql = "INSERT INTO tracker_rule_list (
                        tracker_rule_id, 
                        source_field_id, 
                        source_value_id, 
                        target_field_id, 
                        target_value_id)
                    VALUES (
                        $tracker_rule_id, 
                        $source_field_id, 
                        $source_value_id, 
                        $target_field_id, 
                        $target_value_id)";

            $retrieve = $this->retrieve($sql);
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        $this->commit();
        return $retrieve;
    }
}
