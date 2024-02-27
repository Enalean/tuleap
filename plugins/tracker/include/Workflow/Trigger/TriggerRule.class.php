<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * PHP representation of a TriggerRule
 */
class Tracker_Workflow_Trigger_TriggerRule implements Tracker_IProvideJsonFormatOfMyself // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var int */
    private $id;

    /** @var Tracker_Workflow_Trigger_FieldValue */
    private $target;

    /** @var String */
    private $condition;

    /** @var Tracker_Workflow_Trigger_FieldValue[] */
    private $triggers = [];

    public function __construct(
        $id,
        Tracker_Workflow_Trigger_FieldValue $target,
        $condition,
        array $triggers,
    ) {
        $this->id        = $id;
        $this->target    = $target;
        $this->condition = $condition;
        $this->triggers  = $triggers;
    }

    /**
     * @return Tracker
     */
    public function getTargetTracker()
    {
        return $this->getTarget()->getField()->getTracker();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Tracker_Workflow_Trigger_FieldValue
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return String
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return Tracker_Workflow_Trigger_FieldValue[]
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Array
     */
    public function fetchFormattedForJson()
    {
        return [
            'id'                => $this->getId(),
            'target'            => $this->getTarget()->fetchFormattedForJson(),
            'condition'         => $this->getCondition(),
            'triggering_fields' => $this->fetchTriggersFormattedForJson(),
        ];
    }

    private function fetchTriggersFormattedForJson()
    {
        $json = [];
        foreach ($this->triggers as $trigger) {
            $json[] = $trigger->fetchFormattedForJson();
        }
        return $json;
    }

    /**
     * Format the rule to be presented to user as a followup comment
     *
     * @return String
     */
    public function getAsChangesetComment()
    {
        $trg = [];
        foreach ($this->getTriggers() as $trigger) {
            $trg[] = $trigger->getAsChangesetComment($this->getCondition());
        }
        return dngettext('tuleap-tracker', 'Current artifact children satisfy the following condition:', 'Current artifact children satisfy the following conditions:', count($trg)) .
               '<ul><li>' . implode('</li><li>' . $this->getConditionOperatorLabel() . ' ', $trg) . '</li></ul>';
    }

    private function getConditionOperatorLabel()
    {
        if ($this->getCondition() === 'all_of') {
            return dgettext('tuleap-tracker', 'and');
        }

        return dgettext('tuleap-tracker', 'or');
    }
}
