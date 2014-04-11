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

    /** @var Tracker_Workflow_Trigger_RulesProcessor */
    private $rules_processor;

    /** @var WorkflowBackendLogger */
    private $logger;

    public function __construct(
        Tracker_Workflow_Trigger_RulesDao $dao,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Workflow_Trigger_RulesProcessor $rules_processor,
        WorkflowBackendLogger $logger
    ) {
        $this->dao                 = $dao;
        $this->formelement_factory = $formelement_factory;
        $this->rules_processor     = $rules_processor;
        $this->logger              = $logger;
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

    public function processChildrenTriggers(Tracker_Artifact $parent) {
        $this->logger->start(__METHOD__, $parent->getId());

        $dar_rules = $this->dao->searchForInvolvedRulesForChildrenLastChangeset($parent->getId());
        foreach ($dar_rules as $row) {
            $artifact = Tracker_ArtifactFactory::instance()->getInstanceFromRow($row);
            $rule = $this->getRuleById($row['rule_id']);
            $this->logger->debug("Found matching rule ". json_encode($rule->fetchFormattedForJson()));
            $this->rules_processor->process($artifact, $rule);
        }

        $this->logger->end(__METHOD__, $parent->getId());
    }

    public function processTriggers(Tracker_Artifact_Changeset $changeset) {
        $this->logger->start(__METHOD__, $changeset->getId());

        $dar_rules = $this->dao->searchForInvolvedRulesIdsByChangesetId($changeset->getId());
        foreach ($dar_rules as $row) {
            $rule = $this->getRuleById($row['rule_id']);
            $this->logger->debug("Found matching rule ". json_encode($rule->fetchFormattedForJson()));
            $this->rules_processor->process($changeset->getArtifact(), $rule);
        }

        $this->logger->end(__METHOD__, $changeset->getId());
    }

    /**
     * Duplicates all the triggers from template on project creation
     *
     * @param array $template_trackers
     * @param array $field_mapping
     */
    public function duplicate(array $template_trackers, array $field_mapping) {
        foreach ($template_trackers as $template_tracker) {
            $this->duplicateFromTemplateTracker($template_tracker, $field_mapping);
        }
    }

    private function duplicateFromTemplateTracker(Tracker $template_tracker, array $field_mapping) {
        $trigger_rule_collection = $this->getForTargetTracker($template_tracker);

        foreach ($trigger_rule_collection as $template_trigger_rule) {
            $old_triggers = $template_trigger_rule->getTriggers();

            $new_target   = $this->buildRuleTargetFromTemplateTriggerRule($template_trigger_rule->getTarget(), $field_mapping);
            $new_triggers = $this->buildRuleTriggersFromTemplateTriggerRule($old_triggers, $field_mapping);

            $new_trigger_rule = new Tracker_Workflow_Trigger_TriggerRule(
                    0,
                    $new_target,
                    $template_trigger_rule->getCondition(),
                    $new_triggers
            );

            $this->add($new_trigger_rule);
        }
    }

    private function buildRuleTargetFromTemplateTriggerRule(
        Tracker_Workflow_Trigger_FieldValue $template_trigger_rule_target,
        array $field_mapping
    ) {
        foreach ($field_mapping as $mapping) {
            if ($mapping['from'] === $template_trigger_rule_target->getField()->getId()) {
                $new_field_id = $mapping['to'];
                $target_field = $this->formelement_factory->getFieldById($new_field_id);
                $target_value = $template_trigger_rule_target->getValue();
                $target_value->setId($mapping['values'][$template_trigger_rule_target->getValue()->getId()]);

                break;
            }
        }

        return new Tracker_Workflow_Trigger_FieldValue(
                $target_field,
                $target_value
        );
    }

    private function buildRuleTriggersFromTemplateTriggerRule(array $template_triggers, array $field_mapping) {
        $new_triggers = array();

        foreach ($template_triggers as $template_trigger) {
           $new_triggers[] = $this->buildRuleTargetFromTemplateTriggerRule($template_trigger, $field_mapping);
        }

        return $new_triggers;
    }

}