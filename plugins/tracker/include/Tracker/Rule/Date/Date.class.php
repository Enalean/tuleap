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
require_once(dirname(__FILE__).'/../Tracker_Rule.class.php');
require_once 'Exception.class.php';

/**
 * Date Rule between two dynamic fields
 *
 * For a tracker, if a source field is selected to a specific value,
 * then target field will be constrained to another value.
 *
 */
class Tracker_Rule_Date extends Tracker_Rule {
    const COMPARATOR_EQUALS = '=';
    const COMPARATOR_NOT_EQUALS = '≠';
    const COMPARATOR_LESS_THAN = '<';
    const COMPARATOR_LESS_THAN_OR_EQUALS = '≤';
    const COMPARATOR_GREATER_THAN = '>';
    const COMPARATOR_GREATER_THAN_OR_EQUALS = '≥';

    protected $_allowed_comparators = array(
        self::COMPARATOR_EQUALS,
        self::COMPARATOR_GREATER_THAN,
        self::COMPARATOR_GREATER_THAN_OR_EQUALS,
        self::COMPARATOR_LESS_THAN,
        self::COMPARATOR_LESS_THAN_OR_EQUALS,
        self::COMPARATOR_NOT_EQUALS,
    );

    /**
     *
     * @var int
     */
    protected $source_field_id;

    /**
     *
     * @var int
     */
    protected $target_field_id;

    /**
     *
     * @var string
     */
    protected $comparator;

    public function __construct() {

    }

    /**
     *
     * @param string $comparator
     * @throws Tracker_Rule_Date_Exception
     */
    public function setComparator($comparator) {
        if(! in_array($comparator, $this->_allowed_comparators)) {
            throw new Tracker_Rule_Date_Exception('Invalid comparator');
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


}
?>
