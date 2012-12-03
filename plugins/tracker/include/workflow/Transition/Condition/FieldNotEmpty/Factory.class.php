<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'Dao.class.php';
require_once TRACKER_BASE_DIR .'/workflow/Transition.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/FormElement/Tracker_FormElementFactory.class.php';

class Workflow_Transition_Condition_FieldNotEmpty_Factory {

    private $dao;
    private $element_factory;

    public function __construct(Workflow_Transition_Condition_FieldNotEmpty_Dao $dao, Tracker_FormElementFactory $element_factory) {
        $this->dao             = $dao;
        $this->element_factory = $element_factory;
    }

    /** @return bool */
    public function isFieldUsedInConditions(Tracker_FormElement_Field $field) {
        return $this->dao->isFieldUsed($field->getId());
    }

    public function getFieldNotEmpty(Transition $transition){
        $condition = new Workflow_Transition_Condition_FieldNotEmpty($transition, $this->dao);

        $row = $this->dao->searchByTransitionId($transition->getId())->getRow();
        if ($row) {
            $condition->setField($this->element_factory->getFormElementById($row['field_id']));
        }

        return $condition;
    }

    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition) {
        $condition = null;
        if (isset($xml->field)) {
            $xml_field            = $xml->field;
            $xml_field_attributes = $xml_field->attributes();
            $field                = $xmlMapping[(string)$xml_field_attributes['REF']];

            $condition = new Workflow_Transition_Condition_FieldNotEmpty($transition, $this->dao);
            $condition->setField($field);
        }
        return $condition;
    }

    /**
     * Duplicate the conditions
     */
    public function duplicate(Transition $from_transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type) {
        $this->dao->duplicate($from_transition->getId(), $new_transition_id, $field_mapping);
    }
}
?>