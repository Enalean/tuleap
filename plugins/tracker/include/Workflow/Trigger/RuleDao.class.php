<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class Tracker_Workflow_Trigger_RulesDao extends DataAccessObject
{

    public function searchForTargetByRuleId($rule_id)
    {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "SELECT field.id as field_id, rule.*
                FROM tracker_workflow_trigger_rule_static_value rule
                  INNER JOIN tracker_field_list_bind_static_value lbsv ON (lbsv.id = rule.value_id)
                  INNER JOIN tracker_field field ON (field.id = lbsv.field_id)
                WHERE rule.id = $rule_id";
        return $this->retrieve($sql);
    }

    public function searchForTargetTracker($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT field.id as field_id, rule.*
                FROM tracker_workflow_trigger_rule_static_value rule
                  INNER JOIN tracker_field_list_bind_static_value lbsv ON (lbsv.id = rule.value_id)
                  INNER JOIN tracker_field field ON (field.id = lbsv.field_id)
                WHERE field.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function searchTriggeringTrackersByTargetTrackerID(int $target_tracker_id)
    {
        $target_tracker_id = $this->da->escapeInt($target_tracker_id);
        $sql = "SELECT DISTINCT trigger_field.tracker_id
                FROM tracker_workflow_trigger_rule_static_value AS rule
                JOIN tracker_field_list_bind_static_value AS target_lbsv ON (target_lbsv.id = rule.value_id)
                JOIN tracker_field AS target_field ON (target_field.id = target_lbsv.field_id)
                JOIN tracker_workflow_trigger_rule_trg_field_static_value AS trigger_field_value ON (trigger_field_value.rule_id = rule.id)
                JOIN tracker_field_list_bind_static_value AS lbsv_trigger ON (lbsv_trigger.id = trigger_field_value.value_id)
                JOIN tracker_field AS trigger_field ON (trigger_field.id = lbsv_trigger.field_id)
                WHERE target_field.tracker_id = $target_tracker_id";
        return $this->retrieve($sql);
    }

    public function searchTriggersByFieldId($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);

        $sql     = "SELECT rule_tracker_child.field_id AS field_id
                    FROM tracker_workflow_trigger_rule_trg_field_static_value AS triggering_field
                    INNER JOIN tracker_field_list_bind_static_value AS rule_tracker_child
                      ON (rule_tracker_child.id = triggering_field.value_id)
                    WHERE rule_tracker_child.field_id = $field_id
                    UNION SELECT field_father.field_id AS field_id
                    FROM tracker_workflow_trigger_rule_static_value AS rule_tracker_father
                    INNER JOIN tracker_field_list_bind_static_value AS field_father
                    ON field_father.id = rule_tracker_father.value_id
                    WHERE field_father.field_id = $field_id";

        return $this->retrieve($sql);
    }

    public function searchForTriggeringFieldByRuleId($rule_id)
    {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "SELECT lbsv.field_id, triggering_field.*
                FROM tracker_workflow_trigger_rule_trg_field_static_value triggering_field
                    INNER JOIN tracker_field_list_bind_static_value lbsv ON (lbsv.id = triggering_field.value_id)
                WHERE rule_id = $rule_id";
        return $this->retrieve($sql);
    }

    public function addTarget($value_id, $condition)
    {
        $value_id  = $this->da->escapeInt($value_id);
        $condition = $this->da->quoteSmart($condition);
        $sql = 'INSERT INTO tracker_workflow_trigger_rule_static_value (value_id, rule_condition) VALUES (' . $value_id . ', ' . $condition . ')';
        return $this->updateAndGetLastId($sql);
    }

    public function addTriggeringField($rule_id, $value_id)
    {
        $rule_id  = $this->da->escapeInt($rule_id);
        $value_id  = $this->da->escapeInt($value_id);
        $sql = 'INSERT INTO tracker_workflow_trigger_rule_trg_field_static_value (rule_id, value_id) VALUES (' . $rule_id . ', ' . $value_id . ')';
        return $this->updateAndGetLastId($sql);
    }

    public function deleteTriggeringFieldsByRuleId($rule_id)
    {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "DELETE FROM tracker_workflow_trigger_rule_static_value
                WHERE id = $rule_id";
        return $this->update($sql);
    }

    public function deleteTargetByRuleId($rule_id)
    {
        $rule_id = $this->da->escapeInt($rule_id);
        $sql = "DELETE FROM tracker_workflow_trigger_rule_trg_field_static_value
                WHERE rule_id = $rule_id";
        return $this->update($sql);
    }

    public function searchForInvolvedRulesIdsByChangesetId($changeset_id)
    {
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

    public function searchForInvolvedRulesForChildrenLastChangeset($parent_id)
    {
        $parent_id = $this->da->escapeInt($parent_id);

        $sql = "SELECT trig.rule_id, art_children.*
                FROM tracker_artifact AS parent_art
                  -- get children
                  INNER JOIN tracker_field                        f_children            ON (f_children.tracker_id = parent_art.tracker_id AND f_children.formElement_type = 'art_link' AND f_children.use_it = 1)
                  INNER JOIN tracker_changeset_value              cv_children           ON (cv_children.changeset_id = parent_art.last_changeset_id AND cv_children.field_id = f_children.id)
                  INNER JOIN tracker_changeset_value_artifactlink artlink_children      ON (artlink_children.changeset_value_id = cv_children.id)
                  INNER JOIN tracker_artifact                     art_children          ON (art_children.id = artlink_children.artifact_id)
                  INNER JOIN tracker_hierarchy                    hierarchy_children    ON (hierarchy_children.child_id = art_children.tracker_id AND hierarchy_children.parent_id = parent_art.tracker_id)

                  -- get rules
                  INNER JOIN tracker_changeset_value                               cv          ON (cv.changeset_id = art_children.last_changeset_id)
                  INNER JOIN tracker_changeset_value_list                          cvl         ON (cvl.changeset_value_id = cv.id)
                  INNER JOIN tracker_field_list_bind_static_value                  field_value ON (field_value.id = cvl.bindvalue_id)
                  INNER JOIN tracker_workflow_trigger_rule_trg_field_static_value  trig        ON (trig.value_id = field_value.id)

                WHERE parent_art.id = $parent_id
                GROUP BY trig.rule_id";
        return $this->retrieve($sql);
    }
}
