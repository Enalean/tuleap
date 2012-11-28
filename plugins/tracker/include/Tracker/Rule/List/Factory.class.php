<?php
/**
  * Copyright (c) Enalean, 2012. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */
require_once TRACKER_BASE_DIR.'/Tracker/Rule/List.class.php';

/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_Rule_List_Factory {

    /**
     *
     * @var Tracker_Rule_List_Dao 
     */
    protected $dao;

    /**
     * 
     * @param Tracker_Rule_List_Dao $dao
     */
    public function __construct(Tracker_Rule_List_Dao $dao) {
        $this->dao = $dao;
    }
 
    /**
     * 
     * @param int $source_field_id
     * @param int $target_field_id
     * @param int $tracker_id
     * @param int $source_value
     * @param int $target_value
     * @return Tracker_Rule_List
     */
    public function create($source_field_id, $target_field_id, $tracker_id, $source_value, $target_value) {
        $list_rule = $this->populate(new Tracker_Rule_List(), $tracker_id, $source_field_id, $target_field_id, $source_value, $target_value);
        $rule_id = $this->insert($list_rule);
        
        $list_rule->setId($rule_id);
        
        return $list_rule;
    }
    
    /**
     * 
     * @param Tracker_Rule_List $list_rule
     * @return int The ID of the tracker_Rule created
     */
    private function insert(Tracker_Rule_List $list_rule) {
        return $this->dao->insert($list_rule);
    }

    /**
     * 
     * @param Tracker_Rule_List $list_rule
     * @return bool
     */
    public function delete(Tracker_Rule_List $list_rule) {
        return $this->dao->delete($list_rule);
    }
    
    /**
     * 
     * @param int $rule_id
     * @return Tracker_Rule_List
     */
    public function searchById($rule_id) {
        $rule = $this->dao->searchById($rule_id);

        if(! $rule) {
            return null;
        }

        return $this->populate(new Tracker_Rule_List(), $rule['source_field_id'], $rule['target_field_id'], $rule['tracker_id'], $rule['source_value_id'], $rule['target_value_id']);
    }
    
    /**
     * 
     * @param int $tracker_id
     * @return array An array of Tracker_Rule_List objects
     */
    public function searchByTrackerId($tracker_id) {
        $rules = $this->dao->searchByTrackerId($tracker_id);

        if(! $rules) {
            return array();
        }

        $rules_array = array();

        while ($rule = $rules->getRow()) {
            $list_rule = $this->populate(new Tracker_Rule_List(), $rule['source_field_id'], $rule['target_field_id'], $rule['tracker_id'], $rule['source_value_id'], $rule['target_value_id']);
            $rules_array[] = $list_rule;
        }
        
        return $rules_array;
    }
    
    /**
     * 
     * @param Tracker_Rule_List $list_rule
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param int $source_value
     * @param int $target_value
     * @return \Tracker_Rule_List
     */
    protected function populate(Tracker_Rule_List $list_rule, $tracker_id, $source_field_id, $target_field_id, $source_value, $target_value) {
        
        $list_rule->setTrackerId($tracker_id)
                ->setSourceFieldId($source_field_id)
                ->setTargetFieldId($target_field_id)
                ->setTrackerId($tracker_id)
                ->setSourceValue($source_value)
                ->setTargetValue($target_value);
        
        return $list_rule;
    }
}
?>
