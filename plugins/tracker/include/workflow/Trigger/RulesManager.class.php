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

class Tracker_Workflow_Trigger_RulesManager {
    /** @var Tracker_Workflow_Trigger_RulesDao */
    private $dao;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    public function __construct(Tracker_Workflow_Trigger_RulesDao $dao, Tracker_FormElementFactory $formelement_factory) {
        $this->dao = $dao;
        $this->formelement_factory = $formelement_factory;
    }

    /**
     * Add a new rule in the DB
     *
     * @param Tracker_Workflow_Trigger_TriggerRule $rule
     */
    public function add(Tracker_Workflow_Trigger_TriggerRule $rule) {
        try {
            $this->dao->enableExceptionsOnError();
            $this->dao->startTransaction();
            $rule_id = $this->dao->addTarget(
                $rule->getTarget()->getValue()->getId(),
                $rule->getCondition()
            );
            $rule->setId($rule_id);
            foreach ($rule->getTriggers() as $triggering_field) {
                $this->dao->addTriggeringField($rule_id, $triggering_field->getValue()->getId());
            }
            $this->dao->commit();
        } catch (DataAccessException $exception) {
            throw new Tracker_Workflow_Trigger_Exception_RuleException('Database error: cannot save');
        }
    }

    /**
     * Delete a rule in target tracker
     *
     * @param Tracker $tracker
     * @param Tracker_Workflow_Trigger_TriggerRule $rule
     * @throws Tracker_Workflow_Trigger_Exception_RuleException
     */
    public function delete(Tracker $tracker, Tracker_Workflow_Trigger_TriggerRule $rule) {
        if ($rule->getTargetTracker() != $tracker) {
            throw new Tracker_Workflow_Trigger_Exception_RuleException('Cannot delete rules from another tracker');
        }
        try {
            $this->dao->enableExceptionsOnError();
            $this->dao->startTransaction();
            $this->dao->deleteTriggeringFieldsByRuleId($rule->getId());
            $this->dao->deleteTargetByRuleId($rule->getId());
            $this->dao->commit();
        } catch (DataAccessException $exception) {
            throw new Tracker_Workflow_Trigger_Exception_RuleException('Database error: cannot delete rule');
        }
    }

    /**
     * Return one Rule given its Id
     *
     * @return Tracker_Workflow_Trigger_TriggerRule
     */
    public function getRuleById($rule_id) {
        $dar = $this->dao->searchForTargetByRuleId($rule_id);
        if ($dar && count($dar) == 1) {
            return $this->getInstanceFromRow($dar->current());
        }
        throw new Tracker_Workflow_Trigger_Exception_TriggerDoesntExistException();
    }

    /**
     * Get all rules that applies on a given tracker
     *
     * @param Tracker $tracker
     *
     * @return Tracker_Workflow_Trigger_TriggerRuleCollection
     */
    public function getForTargetTracker(Tracker $tracker) {
        $rules = new Tracker_Workflow_Trigger_TriggerRuleCollection();
        foreach ($this->dao->searchForTargetTracker($tracker->getId()) as $row) {
            $rules->push($this->getInstanceFromRow($row));
        }
        return $rules;
    }

    private function getInstanceFromRow(array $row) {
        return new Tracker_Workflow_Trigger_TriggerRule(
            $row['id'],
            $this->getTarget($row['field_id'], $row['value_id']),
            $row['rule_condition'],
            $this->getTriggers($row['id'])
        );
    }

    private function getTarget($field_id, $value_id) {
        return $this->getFieldValue($field_id, $value_id);
    }

    private function getTriggers($rule_id) {
        $triggers = array();
        foreach ($this->dao->searchForTriggeringFieldByRuleId($rule_id) as $row) {
            $triggers[] = $this->getFieldValue($row['field_id'], $row['value_id']);
        }
        return $triggers;
    }

    private function getFieldValue($field_id, $value_id) {
        $field = $this->formelement_factory->getUsedFormElementFieldById($field_id);
        return new Tracker_Workflow_Trigger_FieldValue(
            $field,
            $this->getValue($field->getAllValues(), $value_id)
        );
    }

    private function getValue(array $all_values, $value_id) {
        foreach ($all_values as $value) {
            if ($value->getId() == $value_id) {
                return $value;
            }
        }
    }
}

?>
