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
 * PHP representation of a TriggerRule
 */
class Tracker_Workflow_Trigger_TriggerRule implements Tracker_IProvideJsonFormatOfMyself {

    /** @var Integer */
    private $id;

    /** @var Tracker_Workflow_Trigger_FieldValue */
    private $target;

    /** @var String */
    private $condition;

    /** @var Tracker_Workflow_Trigger_FieldValue[] */
    private $triggers = array();

    public function __construct(
            $id,
            Tracker_Workflow_Trigger_FieldValue $target,
            $condition,
            array $triggers) {
        $this->id        = $id;
        $this->target    = $target;
        $this->condition = $condition;
        $this->triggers  = $triggers;
    }

    /**
     * @return Integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return Tracker_Workflow_Trigger_FieldValue
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @return String
     */
    public function getCondition() {
        return $this->condition;
    }

    /**
     * @return Tracker_Workflow_Trigger_FieldValue[]
     */
    public function getTriggers() {
        return $this->triggers;
    }

    /**
     * @param Integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return Array
     */
    public function fetchFormattedForJson() {
        return array(
            'id'                => $this->getId(),
            'target'            => $this->getTarget()->fetchFormattedForJson(),
            'condition'         => $this->getCondition(),
            'triggering_fields' => $this->fetchTriggersFormattedForJson()
        );
    }

    private function fetchTriggersFormattedForJson() {
        $json = array();
        foreach ($this->triggers as $trigger) {
            $json[] = $trigger->fetchFormattedForJson();
        }
        return $json;
    }
}

?>
