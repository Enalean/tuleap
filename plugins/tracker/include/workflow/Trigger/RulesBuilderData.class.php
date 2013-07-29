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

class Tracker_Workflow_Trigger_RulesBuilderData {
    const CONDITION_AT_LEAST_ONE = 'at_least_one';
    const CONDITION_ALL_OFF      = 'all_of';

    /**
     * @var Tracker_FormElement_Field_List[]
     */
    private $targets;

    /**
     * @var Tracker_Workflow_Trigger_RulesBuilderTriggeringFields[]
     */
    private $triggering_fields;

    public function __construct(array $targets, array $triggering_fields) {
        $this->targets = $targets;
        $this->triggering_fields = $triggering_fields;
    }

    public function toJson() {
        return json_encode(array(
            'targets'    => $this->getTargets(),
            'conditions' => array(self::CONDITION_AT_LEAST_ONE,  self::CONDITION_ALL_OFF),
            'triggers'   => $this->getTriggers(),
        ));
    }

    private function getTargets() {
        return array_map(array($this, 'getTargetField'), $this->targets);
    }

    private function getTargetField(Tracker_FormElement_Field_List $target) {
        return $target->fetchFormattedForJson();
    }

    private function getTriggers() {
        return array_map(array($this, 'getChildTracker'), $this->triggering_fields);
    }

    private function getChildTracker(Tracker_Workflow_Trigger_RulesBuilderTriggeringFields $triggering_fields) {
        return array(
            'id'     => $triggering_fields->getTracker()->getId(),
            'name'   => $triggering_fields->getTracker()->getName(),
            'fields' => array_map(array($this, 'getTargetField'), $triggering_fields->getFields())
        );
    }
}

?>
