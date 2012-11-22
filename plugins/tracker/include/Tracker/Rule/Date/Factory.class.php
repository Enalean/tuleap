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

require_once('../dao/Tracker_RuleDao.class.php');
require_once('Date.class.php');
require_once('../../FormElement/Tracker_FormElementFactory.class.php');
require_once('../../TrackerFactory.class.php');


/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_Rule_Date_Factory {

    /**
     *
     * @var Tracker_RuleDao 
     */
    protected $rules_dao;
    
    /**
     *
     * @var TrackerFactory 
     */
    protected $tracker_factory = null;
    
    /**
     *
     * @var Tracker_FormElementFactory 
     */
    protected $tracker_form_element_factory = null;

    /**
     * 
     * @param DataAccessObject $dao
     */
    function __construct(&$dao) {
        $this->dao =& $dao;
    }
 
    /**
     * 
     * @param Tracker $tracker
     * @param Tracker_FormElement_Field_Date $source_field
     * @param Tracker_FormElement_Field_Date $target_field
     * @return \Tracker_Rule_Date
     */
    public function create(Tracker $tracker, Tracker_FormElement_Field $source_field, Tracker_FormElement_Field $target_field, Tracker $tracker) {
        return $this->populate(new Tracker_Rule_Date(), $source_field, $target_field, $tracker);
    }
    
    /**
     * 
     * @param Tracker_Rule_Date $date_rule
     * @return bool
     */
    public function save(Tracker_Rule_Date $date_rule) {
        return $this->rules_dao->createDateRule($date_rule);
    }

    /**
     * 
     * @param Tracker_Rule_Date $date_rule
     * @return bool
     */
    public function delete(Tracker_Rule_Date $date_rule) {
        return $this->rules_dao->delete($date_rule);
    }
    
    /**
     * 
     * @param int $rule_id
     * @return Tracker_Rule_Date
     */
    public function searchById($rule_id) {
        $rule = $this->rules_dao->searchDateRulesById($rule_id);
        if(!is_array($rule) || count($rule) !== 1) {
            //do something bad
        }
        
        $source_field = $this->getTrackerFormElementFactory()
                ->getFieldById($rule['source_field_id']);
        $target_field = $this->getTrackerFormElementFactory()
                ->getFieldById($rule['target_field_id']);
        $tracker = $this->getTrackerFactory()
                ->getTrackerById($rule['tracker_id']);
        
        return $this->populate(new Tracker_Rule_Date(), $source_field, $target_field, $tracker);
    }
    
    /**
     * 
     * @param int $tracker_id
     * @return array An array of Tracker_Rule_Date objects
     */
    public function searchByTrackerId($tracker_id) {
        $rules = $this->rules_dao->searchDateRulesByTrackerId($tracker_id);
        
        if(!is_array($rules) || count($rules) < 1) {
            return array();
        }
        
        $rules_array = array();
        
        foreach ($rules as $rule) {
            $source_field = $this->getTrackerFormElementFactory()
                    ->getFieldById($rule['source_field_id']);
            $target_field = $this->getTrackerFormElementFactory()
                    ->getFieldById($rule['target_field_id']);
            $tracker = $this->getTrackerFactory()
                    ->getTrackerById($rule['tracker_id']);

            $date_rule = $this->populate(new Tracker_Rule_Date(), $source_field, $target_field, $tracker);
            $rules_array[] = $date_rule;
        }
        
        return $rules_array;
    }
    
    /**
     * 
     * @return TrackerFactory
     */
    public function getTrackerFactory() {
        if($this->tracker_factory === null) {
            $this->tracker_factory = new TrackerFactory();
        }
        
        return $this->tracker_factory;
    }
    
    /**
     * 
     * @param TrackerFactory $factory
     * @return \Tracker_Rule_Date_Factory
     */
    public function setTrackerFactory(TrackerFactory $factory) {
        $this->tracker_factory = $factory;
        return $this;
    }
    
    /**
     * 
     * @return Tracker_FormElementFactory
     */
    public function getTrackerFormElementFactory() {
        if($this->tracker_form_element_factory === null) {
            $this->tracker_factory = new Tracker_FormElementFactory();
        }
        
        return $this->tracker_form_element_factory;
    }
    
    /**
     * 
     * @param Tracker_FormElementFactory $factory
     * @return \Tracker_Rule_Date_Factory
     */
    public function setTrackerFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->tracker_form_element_factory = $factory;
        return $this;
    }
    


    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping) {
    }

    public function getInstanceFromXML($xml, &$xmlMapping, $tracker) {
    }

    protected function populate(Tracker_Rule_Date $date_rule, Tracker $tracker, Tracker_FormElement_Field $source_field, Tracker_FormElement_Field $target_field) {
        
        $date_rule->setTracker($tracker)
                ->setSourceField($source_field)
                ->setTargetField($target_field)
                ->setTracker($tracker);
        
        return $date_rule;
    }
  

}
?>