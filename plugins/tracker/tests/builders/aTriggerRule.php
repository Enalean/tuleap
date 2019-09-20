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

/**
 * @return Test_Tracker_Workflow_Trigger_TriggerRuleBuilder
 */
function aTriggerRule()
{
    return new Test_Tracker_Workflow_Trigger_TriggerRuleBuilder();
}

class Test_Tracker_Workflow_Trigger_TriggerRuleBuilder
{

    private $id;
    private $target;
    private $condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;
    private $triggering_fields = array();

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function __construct()
    {
        $this->target = mock('Tracker_Workflow_Trigger_FieldValue');
    }

    /**
     * @param Tracker_Workflow_Trigger_FieldValue $target
     * @return Test_Tracker_Workflow_Trigger_TriggerRuleBuilder
     */
    public function applyValue(Tracker_Workflow_Trigger_FieldValue $target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @param Tracker_Workflow_Trigger_FieldValue $trigger
     * @return Test_Tracker_Workflow_Trigger_TriggerRuleBuilder
     */
    public function childHas(Tracker_Workflow_Trigger_FieldValue $trigger)
    {
        $this->triggering_fields[] = $trigger;
        return $this;
    }

    /**
     * @return Test_Tracker_Workflow_Trigger_TriggerRuleBuilder
     */
    public function whenAtLeastOne()
    {
        $this->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE;
        return $this;
    }

    /**
     * @return Test_Tracker_Workflow_Trigger_TriggerRuleBuilder
     */
    public function whenAllOf()
    {
        $this->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;
        return $this;
    }

    /**
     * @return Tracker_Workflow_Trigger_TriggerRule
     */
    public function build()
    {
        return new Tracker_Workflow_Trigger_TriggerRule(
            $this->id,
            $this->target,
            $this->condition,
            $this->triggering_fields
        );
    }
}
