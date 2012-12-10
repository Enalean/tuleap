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
require_once 'Tracker_Rule.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Rule/Date/InvalidComparatorException.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Rule/Date/MissingComparatorException.class.php';

/**
 * Date Rule between two dynamic fields
 *
 * For a tracker, if a source field is selected to a specific value,
 * then target field will be constrained to another value.
 *
 */
class Tracker_Rule_Date extends Tracker_Rule {

    const COMPARATOR_EQUALS                 = '=';
    const COMPARATOR_NOT_EQUALS             = '≠';
    const COMPARATOR_LESS_THAN              = '<';
    const COMPARATOR_LESS_THAN_OR_EQUALS    = '≤';
    const COMPARATOR_GREATER_THAN           = '>';
    const COMPARATOR_GREATER_THAN_OR_EQUALS = '≥';

    public static $allowed_comparators = array(
        self::COMPARATOR_LESS_THAN,
        self::COMPARATOR_LESS_THAN_OR_EQUALS,
        self::COMPARATOR_EQUALS,
        self::COMPARATOR_GREATER_THAN_OR_EQUALS,
        self::COMPARATOR_GREATER_THAN,
        self::COMPARATOR_NOT_EQUALS,
    );

    /** @return mixed */
    public function exportToSOAP() {
        return array(
            'source_field_id' => $this->getSourceFieldId(),
            'target_field_id' => $this->getTargetFieldId(),
            'comparator'      => $this->getComparator(),
        );
    }

    /**
     *
     * @var string
     */
    protected $comparator;

    /**
     *
     * @param string $comparator
     * @throws Tracker_Rule_Date_Exception
     */
    public function setComparator($comparator) {
        if(! in_array($comparator, self::$allowed_comparators)) {
            throw new Tracker_Rule_Date_InvalidComparatorException();
        }

        $this->comparator = $comparator;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getComparator() {
        return $this->comparator;
    }
    
    /**
     * 
     * Checks that two given values satisfy the rule 
     * 
     * @param string $source_value
     * @param string $target_value
     * @return boolean
     */
    public function validate($source_value, $target_value) {
        
        //if one of the value is empty then return true
        if ($source_value == null || $target_value == null) {
            return true;
        }
        
        $source_parts = explode('-', $source_value);
        $target_parts = explode('-', $target_value);
        
        $source_date = mktime(0, 0, 0, $source_parts[1], $source_parts[2], $source_parts[0]);
        $target_date = mktime(0, 0, 0, $target_parts[1], $target_parts[2], $target_parts[0]);
        
        switch ($this->getComparator()) {
            case self::COMPARATOR_EQUALS :
                return $source_date == $target_date;
            case self::COMPARATOR_NOT_EQUALS :
                return $source_date != $target_date;
            case self::COMPARATOR_GREATER_THAN :
                return $source_date > $target_date;
            case self::COMPARATOR_GREATER_THAN_OR_EQUALS :
                return $source_date >= $target_date;
            case self::COMPARATOR_LESS_THAN :
                return $source_date < $target_date;
            case self::COMPARATOR_LESS_THAN_OR_EQUALS :
                return $source_date <= $target_date;
            default :
                throw new Tracker_Rule_Date_MissingComparatorException();
        }
    }
}
?>
