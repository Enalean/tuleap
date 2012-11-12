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

require_once 'ConditionsCollection.class.php';
require_once dirname(__FILE__) . '/Condition/dao/FieldNotEmptyDao.class.php';

class Workflow_Transition_ConditionFactory {
    
    private $collection;

    public function __construct() {
        $this->setCollection( new Workflow_Transition_ConditionsCollection());
    }
    
    /**
     * @return Workflow_Transition_ConditionsCollection
     */
    public function getConditions(Transition $transition) {
        $this->collection->add(new Workflow_Transition_Condition_Permissions($transition));
        $this->collection->add($this->getFieldNotEmpty($transition));
        return $this->collection;
    }
    
    private function getFieldNotEmpty($transition){
        
        $field = new Workflow_Transition_Condition_FieldNotEmpty($transition);
        
        $condition = $this->getDao()->searchByTransitionId($transition->getId());
        
        if($condition){
            $row = $condition->getRow();
            $field->setFieldId($row['field_id']) ;
        }
        
        return $field;
    }
    /**
     * Saves a new condition.
     * @param Transition $transition
     * @param int $field_id
     * @return int The ID of the newly created condition
     */
    public function addCondition($transition, $field_id) {
        return $this->getDao()->create($transition->getId(), $field_id);
    }
    
    public function getDao() {
        return new Transition_Condition_FieldNotEmptyDao();
    }
    
    /**
     * Create all conditions on a transition from a XML
     *
     * @return Workflow_Transition_ConditionsCollection
     */
    public function getAllInstancesFromXML($xml, &$xmlMapping, Transition $transition) {
        $conditions = new Workflow_Transition_ConditionsCollection();
        if ($this->isLegacyXML($xml)) {
            if ($xml->permissions) {
                $conditions->add($this->createConditionPermissionsFromXML($xml, $transition));
            }
        } else if ($xml->conditions) {
            foreach ($xml->conditions->condition as $xml_condition) {
                $conditions->add($this->getInstanceFromXML($xml_condition, $xmlMapping, $transition));
            }
        }
        return $conditions;
    }
    
    private function getCollection() {
        return $this->collection;
    }
    
    private function setCollection(Workflow_Transition_ConditionsCollection $collection) {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Creates a transition Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported workflow
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     *
     * @return Workflow_Transition_Condition The condition object, or null if error
     */
    private function getInstanceFromXML($xml, &$xmlMapping, Transition $transition) {
        $type      = (string)$xml['type'];
        $condition = null;
        switch ($type) {
            case 'perms':
                if ($xml->permissions) {
                    $condition = $this->createConditionPermissionsFromXML($xml, $transition);
                }
                break;
        }
        return $condition;
    }

    /**
     * Say if we are using a deprecated xml file.
     *
     * Before Tuleap 5.7, permissions element was located here:
     *
     * <transition>
     *   ...
     *   <permissions>
     *     ...
     *   </permissions>
     * </transition>
     *
     * instead of:
     *
     * <transition>
     *   ...
     *   <conditions>
     *     <condition type="perm">
     *       <permissions>
     *         ...
     *       </permissions>
     *     </condition>
     *   </conditions>
     * </transition>
     *
     * @see getInstanceFromXML
     *
     * @return bool
     */
    private function isLegacyXML($xml) {
        return isset($xml->permissions);
    }

    /**
     * @return Workflow_Transition_Condition_Permissions
     */
    private function createConditionPermissionsFromXML($xml, Transition $transition) {
        $authorized_ugroups_keyname = array();
        foreach ($xml->permissions->permission as $perm) {
            $ugroup = (string)$perm['ugroup'];
            if (isset($GLOBALS['UGROUPS'][$ugroup])) {
                $authorized_ugroups_keyname[] = $GLOBALS['UGROUPS'][$ugroup];
            }
        }
        $condition = new Workflow_Transition_Condition_Permissions($transition);
        $condition->setAuthorizedUgroupsKeyname($authorized_ugroups_keyname);
        return $condition;
    }
}
?>
