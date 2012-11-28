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
require_once dirname(__FILE__).'/../../../builders/all.php';
require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date.class.php';

class Tracker_Rule_Date_DateTest extends TuleapTestCase {

    /**
     *
     * @var Tracker_Rule_Date
     */
   protected $date_rule;



   public function setUp() {
        parent::setUp();
        $this->date_rule = new Tracker_Rule_Date();
    }

    /*
     * Source Field tests
     */
    public function testSetSourceFieldIdReturnsModelObject() {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetSourceFieldIdReturnsFieldIdSet() {
        $this->date_rule->setSourceFieldId(45);
        $this->assertEqual(45, $this->date_rule->getSourceFieldId());
    }

    /*
     * Target Field tests
     */
    public function testSetTargetFieldIdReturnsModelObject() {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetTargetFieldIdReturnsTargetIdSet() {
        $this->date_rule->setTargetFieldId(45);
        $this->assertEqual(45, $this->date_rule->getTargetFieldId());
    }

    /*
     * Tracker Field tests
     */
    public function testSetTrackerFieldIdReturnsModelObject() {
        $set = $this->date_rule->setTrackerId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetTrackerFieldIdReturnsTrackerIdSet() {
        $this->date_rule->setTrackerId(45);
        $this->assertEqual(45, $this->date_rule->getTrackerId());
    }
    
    /*
     * Comparator Field tests
     */
    public function testSetComparatorReturnsModelObject() {
        $set = $this->date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetComparatorReturnsComparatorSet() {
        $this->date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        $this->assertEqual(Tracker_Rule_Date::COMPARATOR_EQUALS, $this->date_rule->getComparator());
    }
    
    public function testSetComparatorWillNotAllowRandomComparators() {
        $this->expectException('Tracker_Rule_Date_InvalidComparatorException');
        $this->date_rule->setComparator('not a comparator');
    }
    
    public function testValidateReturnsTrueForTwoEqualDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        
        $source_value = '2012-11-15';
        $target_value = '2012-11-15';
        
        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsfalseForTwoEqualDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        
        $source_value = '2013-11-15';
        $target_value = '2012-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsTrueForTwoUnequalDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);
        
        $source_value = '2012-11-15';
        $target_value = '2018-11-15';
        
        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsfalseForTwoUnequalDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);
        
        $source_value = '2012-11-15';
        $target_value = '2012-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsTrueForGreaterDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);
        
        $source_value = '2012-11-17';
        $target_value = '2012-11-16';
        
        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsFalseForGreaterDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);
        
        $source_value = '2013-10-15';
        $target_value = '2014-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsTrueForGreaterOrEqualDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);
        
        $source_value = '2012-11-19';
        $target_value = '2012-11-16';
        
        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsFalseForGreaterOrEqualDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);
        
        $source_value = '2013-12-15';
        $target_value = '2018-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsTrueForLessDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);
        
        $source_value = '2012-11-11';
        $target_value = '2012-11-14';
        
        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsFalseForLessDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);
        
        $source_value = '2018-12-15';
        $target_value = '2015-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsTrueForLessOrEqualDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);
        
        $source_value = '2012-11-15';
        $target_value = '2012-11-19';
        
        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateReturnsFalseForLessOrEqualDates() {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);
        
        $source_value = '2016-12-15';
        $target_value = '2012-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
    
    public function testValidateThrowsAnExceptionWhenNoComparatorIsSet() {
        $this->expectException('Tracker_Rule_Date_MissingComparatorException');
        $date_rule = new Tracker_Rule_Date();

        $source_value = '2015-12-15';
        $target_value = '2012-11-15';
        
        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
}
?>
