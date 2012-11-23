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
require_once('Dao.class.php');
require_once('Date.class.php');

/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_Rule_Date_Factory {

    /**
     *
     * @var Tracker_Rule_Date_Dao 
     */
    protected $dao;

    /** @var Tracker_FormElementFactory */
    private $element_factory;

    /**
     * 
     * @param DataAccessObject $dao
     */
    function __construct(Tracker_Rule_Date_Dao $dao, Tracker_FormElementFactory $element_factory) {
        $this->dao = $dao;
        $this->element_factory = $element_factory;
    }
 
    /**
     * 
     * @param int $source_field_id
     * @param int $target_field_id
     * @param int $tracker_id
     * @param string $comparator
     * @return Tracker_Rule_Date
     */
    public function create($id, $source_field_id, $target_field_id, $tracker_id, $comparator) {
        $date_rule = $this->populate(new Tracker_Rule_Date(), $id, $tracker_id, $source_field_id, $target_field_id, $comparator);
        $rule_id = $this->insert($date_rule);
        
        $date_rule->setId($rule_id);
        
        return $date_rule;
    }
    
    /**
     * 
     * @param Tracker_Rule_Date $date_rule
     * @return int The ID of the tracker_Rule created
     */
    public function insert(Tracker_Rule_Date $date_rule) {
        return $this->dao->insert($date_rule);
    }

    /**
     * 
     * @param Tracker_Rule_Date $date_rule
     * @return bool
     */
    public function delete(Tracker_Rule_Date $date_rule) {
        return $this->dao->delete($date_rule);
    }
    
    /**
     * 
     * @param int $rule_id
     * @return Tracker_Rule_Date
     */
    public function searchById($rule_id) {
        $rule = $this->dao->searchById($rule_id)->getRow();
        if (!$rule) {
            return null;
        }

        return $this->populate(
            new Tracker_Rule_Date(),
            $rule['id'],
            $rule['tracker_id'],
            $rule['source_field_id'],
            $rule['target_field_id'],
            $rule['comparator']
        );
    }
    
    /**
     * 
     * @param int $tracker_id
     * @return array An array of Tracker_Rule_Date objects
     */
    public function searchByTrackerId($tracker_id) {
        $rules = $this->dao->searchByTrackerId($tracker_id);

        if(! $rules) {
            return array();
        }

        $rules_array = array();

        while ($rule = $rules->getRow()) {
            $comparartor = $rule['comparator'];
            $date_rule = $this->populate(new Tracker_Rule_Date(), $rule['id'], $rule['tracker_id'], $rule['source_field_id'], $rule['target_field_id'], $comparartor);
            $rules_array[] = $date_rule;
        }
        
        return $rules_array;
    }
    
    /**
     * 
     * @param Tracker_Rule_Date $date_rule
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param string $comparator
     * @return \Tracker_Rule_Date
     */
    protected function populate(Tracker_Rule_Date $date_rule, $id, $tracker_id, $source_field_id, $target_field_id, $comparator) {
        
        $source_field = $this->element_factory->getFormElementById($source_field_id);
        $target_field = $this->element_factory->getFormElementById($target_field_id);
        $date_rule->setTrackerId($tracker_id)
                ->setId($id)
                ->setSourceFieldId($source_field_id)
                ->setSourceField($source_field)
                ->setTargetFieldId($target_field_id)
                ->setTargetField($target_field)
                ->setTrackerId($tracker_id)
                ->setComparator($comparator);
        
        return $date_rule;
    }
}
?>
