<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
 
require_once('WorkflowFactory.class.php');

class Workflow {
    
    public $workflow_id;
    public $tracker_id;
    public $field_id;
    public $transitions;
    public $is_used;
    
    /**
     * @var Tracker_Artifact
     */
    protected $artifact = null;
    
    /**
     * @var Tracker_FormElement_Field
     */
    protected $field = null;
    
    public function __construct($workflow_id, $tracker_id, $field_id, $is_used, $transitions = null) {
        $this->workflow_id      = $workflow_id;
        $this->tracker_id = $tracker_id;
        $this->field_id   = $field_id;
        $this->is_used = $is_used;
        $this->transitions   = $transitions;
    }
    
    /**
     * Set artifact
     *
     * @param Tracker_Artifact $artifact artifact the workflow control
     */
    public function setArtifact(Tracker_Artifact $artifact) {
        $this->artifact = $artifact;
    }
    
    /**
     * Set field
     *
     * @param Tracker_FormElement_Field $field Field
     */
    public function setField(Tracker_FormElement_Field $field) {
        $this->field    = $field;
        $this->field_id = $field->getId();
    }
    
    /**
     * @return string
     */
    public function getId() {
        return $this->workflow_id;
    }
    
    /**
     * @return string
     */
    public function getTrackerId() {
        return $this->tracker_id;
    }
    
    /**
     * @return string
     */
    public function getFieldId() {
        return $this->field_id;
    }
    
    /**
     * @return Tracker_FormElement_Field
     */
    public function getField() {
        if (!$this->field) {
            $this->field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId());
        }
        return $this->field;
    }
    
    /**
     * Return the tracker of this workflow
     *
     * @return Tracker
     */
    public function getTracker() {
        return TrackerFactory::instance()->getTrackerByid($this->tracker_id);
    }
    
    /**
     * @return array of Transition
     */
    public function getTransitions() {
        if ($this->transitions === null) {
            $this->transitions = WorkflowFactory::instance()->getTransitions($this);
        }
        return $this->transitions;
    }
    
    /**
     * Return transition corresponding to parameters
     *
     * @param Integer $field_value_id_from
     * @param Integer $field_value_id_to
     * 
     * @return Transition or null if no transition match
     */
    public function getTransition($field_value_id_from, $field_value_id_to) {
        foreach ($this->getTransitions() as $transition) {
            $from = $transition->getFieldValueFrom();
            if ($from === null && $field_value_id_from === null || $from !== null && $from->getId() == $field_value_id_from) {
                if ($transition->getFieldValueTo()->getId() == $field_value_id_to) {
                    return $transition;
                }
            }
        }
    }
    
     /**
     * @return string
     */
    public function getIsUsed() {
        return $this->is_used;
    }
    
    public function isTransitionExist($field_value_from, $field_value_to) {
        if ($field_value_from != $field_value_to) {
            $transitions = $this->getTransitions();
            
            if ($transitions != null) {
                foreach ($transitions as $transition) {
                    
                    if ($transition->equals(new Transition(0, $this->workflow_id, $field_value_from, $field_value_to))) {
                         return true;
                    }
                }
                
                return false;
            } else {
                return false;
            }
        } else {
            // a non transition (from a value A to the same value A) is always valid
            return true;
        }
    }
    
    public function getTransitionIdFromTo($field_value_from, $field_value_to) {
        $res = WorkflowFactory::instance()->getTransitionIdFromTo($this->workflow_id, $field_value_from, $field_value_to);    
        $row = $res->getRow();
        return $row['transition_id'];
    }
    
    public function hasTransitions() {
         if ($this->getTransitions() === array()) {
             return false;
         }else {
             return true;
         }
    }
    
    public function duplicate($to_tracker_id, $from_id, $to_id, $values) {
        return WorkflowFactory::instance()->duplicate($this->workflow_id, $to_tracker_id, $from_id, $to_id, $values, $this->getIsUsed(), $this->getTransitions());
    }
    
    /**
     * Set the tracker of this workflow
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function setTracker($tracker) {
        $this->tracker_id = $tracker;
    }
    
    public function exportToXml(&$root, $xmlMapping) {
           $root->addChild('field_id')->addAttribute('REF', array_search($this->field_id, $xmlMapping));
           $root->addChild('is_used', $this->is_used);
           $child = $root->addChild('transitions');
           $transitions = $this->getTransitions($this->workflow_id);
           foreach ($transitions as $transition) {
               $grand_child = $child->addChild('transition'); 
               if ($transition->getFieldValueFrom() == null) {
                   $grand_child->addChild('from_id')->addAttribute('REF', 'null');
               }else {
                   $grand_child->addChild('from_id')->addAttribute('REF', array_search($transition->getFieldValueFrom()->getId(), $xmlMapping['values']));
               }
               $grand_child->addChild('to_id')->addAttribute('REF', array_search($transition->getFieldValueTo()->getId(), $xmlMapping['values']));
           }
    }
    
    /**
     * Execute actions before transition happens (if there is one)
     * 
     * @param Array $fields_data Request field data (array[field_id] => data)
     * 
     * @return void
     */
    public function before(array &$fields_data) {
        if (isset($fields_data[$this->getFieldId()])) {
            $oldValues = $this->artifact->getLastChangeset()->getValue($this->getField());
            $from      = null;
            if ($oldValues) {
                if ($v = $oldValues->getValue()) {
                    // Todo: what about multiple values in the changeset?
                    list(,$from) = each($v);
                    $from = (int)$from;
                }
            }
            $to         = (int)$fields_data[$this->getFieldId()];
            $transition = $this->getTransition($from, $to);
            if ($transition) {
                $transition->before($fields_data);
            }
        }
    }
}
?>
