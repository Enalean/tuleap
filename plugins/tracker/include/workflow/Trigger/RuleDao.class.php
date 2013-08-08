<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Workflow_Trigger_RulesDao extends DataAccessObject {

    public function searchForTargetByRuleId($rule_id) {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "SELECT field.id as field_id, rule.*
                FROM tracker_workflow_trigger_rule_static_value rule
                  INNER JOIN tracker_field_list_bind_static_value lbsv ON (lbsv.id = rule.value_id)
                  INNER JOIN tracker_field field ON (field.id = lbsv.field_id)
                WHERE rule.id = $rule_id";
        return $this->retrieve($sql);
    }

    public function searchForTargetTracker($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT field.id as field_id, rule.*
                FROM tracker_workflow_trigger_rule_static_value rule
                  INNER JOIN tracker_field_list_bind_static_value lbsv ON (lbsv.id = rule.value_id)
                  INNER JOIN tracker_field field ON (field.id = lbsv.field_id)
                WHERE field.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function searchForTriggeringFieldByRuleId($rule_id) {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "SELECT lbsv.field_id, triggering_field.*
                FROM tracker_workflow_trigger_rule_trg_field_static_value triggering_field
                    INNER JOIN tracker_field_list_bind_static_value lbsv ON (lbsv.id = triggering_field.value_id)
                WHERE rule_id = $rule_id";
        return $this->retrieve($sql);
    }

    public function addTarget($value_id, $condition) {
        $value_id  = $this->da->escapeInt($value_id);
        $condition = $this->da->quoteSmart($condition);
        $sql = 'INSERT INTO tracker_workflow_trigger_rule_static_value (value_id, rule_condition) VALUES ('.$value_id.', '.$condition.')';
        return $this->updateAndGetLastId($sql);
    }

    public function addTriggeringField($rule_id, $value_id) {
        $rule_id  = $this->da->escapeInt($rule_id);
        $value_id  = $this->da->escapeInt($value_id);
        $sql = 'INSERT INTO tracker_workflow_trigger_rule_trg_field_static_value (rule_id, value_id) VALUES ('.$rule_id.', '.$value_id.')';
        return $this->updateAndGetLastId($sql);
    }

    public function deleteTriggeringFieldsByRuleId($rule_id) {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "DELETE FROM tracker_workflow_trigger_rule_static_value
                WHERE id = $rule_id";
        return $this->update($sql);
    }

    public function deleteTargetByRuleId($rule_id) {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "DELETE FROM tracker_workflow_trigger_rule_trg_field_static_value
                WHERE rule_id = $rule_id";
        return $this->update($sql);
    }

    public function searchForInvolvedRulesIdsByChangesetId($changeset_id) {
        $changeset_id = $this->da->escapeInt($changeset_id);

        $sql = "SELECT field_value.field_id, trig.*
                    FROM tracker_workflow_trigger_rule_trg_field_static_value AS trig
                      INNER JOIN tracker_field_list_bind_static_value AS field_value
                            ON trig.value_id = field_value.id
                      INNER JOIN tracker_changeset_value AS cv
                            ON (cv.field_id = field_value.field_id
                                    AND cv.has_changed = 1
                                    AND cv.changeset_id = $changeset_id)
                      INNER JOIN tracker_changeset_value_list AS cvl
                            ON (cvl.changeset_value_id = cv.id
                                    AND cvl.bindvalue_id = field_value.id)";

        return $this->retrieve($sql);
    }
}

?>
