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
require_once TRACKER_BASE_DIR.'/Tracker/Rule/Date.class.php';
require_once 'Dao.class.php';
/**
 * Factory of rules
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
     * @param Tracker_FormElementFactory $element_factory
     */
    public function __construct(Tracker_Rule_Date_Dao $dao, Tracker_FormElementFactory $element_factory = null) {
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
    public function create($source_field_id, $target_field_id, $tracker_id, $comparator) {
        $rule_id   = $this->insert($tracker_id, $source_field_id, $target_field_id, $comparator);
        $date_rule = $this->populate(new Tracker_Rule_Date(), $rule_id, $tracker_id, $source_field_id, $target_field_id, $comparator);

        return $date_rule;
    }
    
    /**
     * 
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param string $comparator
     * @throws Tracker_Rule_Date_Exception
     */
    public function insert($tracker_id, $source_field_id, $target_field_id, $comparator) {
        if (!in_array($comparator, Tracker_Rule_Date::$allowed_comparators)) {
            throw new Tracker_Rule_Date_Exception('Invalid Comparator');
        }
        
        return $this->dao->insert($tracker_id, $source_field_id, $target_field_id, $comparator);  
    }

    /**
     * @return bool
     */
    public function deleteById($tracker_id, $rule_id) {
        return $this->dao->deleteById($tracker_id, $rule_id);
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
            $rules_array[] = $this->populate(
                new Tracker_Rule_Date(),
                $rule['id'],
                $rule['tracker_id'],
                $rule['source_field_id'],
                $rule['target_field_id'],
                $rule['comparator']
            );
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
