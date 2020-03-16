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
require_once __DIR__ . '/../../../bootstrap.php';
class Tracker_Rule_Date_DateTest extends TuleapTestCase
{

    /**
     *
     * @var Tracker_Rule_Date
     */
    protected $date_rule;



    public function setUp()
    {
        parent::setUp();
        $this->date_rule = new Tracker_Rule_Date();
    }

    /*
     * Source Field tests
     */
    public function testSetSourceFieldIdReturnsModelObject()
    {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetSourceFieldIdReturnsFieldIdSet()
    {
        $this->date_rule->setSourceFieldId(45);
        $this->assertEqual(45, $this->date_rule->getSourceFieldId());
    }

    /*
     * Target Field tests
     */
    public function testSetTargetFieldIdReturnsModelObject()
    {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetTargetFieldIdReturnsTargetIdSet()
    {
        $this->date_rule->setTargetFieldId(45);
        $this->assertEqual(45, $this->date_rule->getTargetFieldId());
    }

    /*
     * Tracker Field tests
     */
    public function testSetTrackerFieldIdReturnsModelObject()
    {
        $set = $this->date_rule->setTrackerId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetTrackerFieldIdReturnsTrackerIdSet()
    {
        $this->date_rule->setTrackerId(45);
        $this->assertEqual(45, $this->date_rule->getTrackerId());
    }

    /*
     * Comparator Field tests
     */
    public function testSetComparatorReturnsModelObject()
    {
        $set = $this->date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetComparatorReturnsComparatorSet()
    {
        $this->date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        $this->assertEqual(Tracker_Rule_Date::COMPARATOR_EQUALS, $this->date_rule->getComparator());
    }

    public function testSetComparatorWillNotAllowRandomComparators()
    {
        $this->expectException('Tracker_Rule_Date_InvalidComparatorException');
        $this->date_rule->setComparator('not a comparator');
    }

    public function testValidateReturnsTrueForTwoEqualDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForTwoUnequalDatesWithEquals()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2013-11-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForTwoUnequalDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2018-11-15';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsfalseForTwoUnequalDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2012-11-17';
        $target_value = '2012-11-16';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15';
        $target_value = '2014-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2012-11-19';
        $target_value = '2012-11-16';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterOrEqualDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15';
        $target_value = '2018-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2012-11-11';
        $target_value = '2012-11-14';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15';
        $target_value = '2015-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-19';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessOrEqualDates()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2016-12-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateThrowsAnExceptionWhenNoComparatorIsSet()
    {
        $this->expectException('Tracker_Rule_Date_MissingComparatorException');
        $date_rule = new Tracker_Rule_Date();

        $source_value = '2015-12-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForTwoEqualDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2012-11-15 14:58';
        $target_value = '2012-11-15 14:58';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForDateAndDateTime()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15 14:58';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForTwoUnequalDateTimesWithEquals()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2013-11-15 14:47';
        $target_value = '2013-11-15 14:48';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForUnequalDateTimeAndDateWithEquals()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2013-11-14';
        $target_value = '2013-11-15 14:48';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForTwoUnequalDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15 11:02';
        $target_value = '2012-11-15 11:03';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsfalseForTwoUnequalDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15 12:25';
        $target_value = '2012-11-15 12:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForDateTimeAndDateWithSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15 12:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2012-11-17 03:25';
        $target_value = '2012-11-16 14:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15 15:23';
        $target_value = '2014-11-15 14:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateThanDateTime()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15 15:23';
        $target_value = '2014-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateTimeThanDate()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15';
        $target_value = '2014-11-15 14:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateTimeThanDateSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2014-11-15';
        $target_value = '2014-11-15 14:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2012-11-19 14:45';
        $target_value = '2012-11-16 23:02';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterOrEqualDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 10:25';
        $target_value = '2018-11-15 03:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterOrEqualDateTimesSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 03:25';
        $target_value = '2013-12-15 03:26';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDateTimesSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 10:25';
        $target_value = '2013-12-15 03:25';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDateTimesSameTime()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 10:25';
        $target_value = '2013-12-15 10:25';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2012-11-11 14:25';
        $target_value = '2012-11-14 14:25';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 14:35';
        $target_value = '2015-11-15 16:28';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessDateTimeSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 17:35';
        $target_value = '2018-12-15 16:28';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessDateTimeSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 17:35';
        $target_value = '2018-12-15 18:28';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForEqualDateTimeSameDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 17:35';
        $target_value = '2018-12-15 17:35';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15 12:35';
        $target_value = '2012-11-19 12:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessOrEqualDateTimes()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15 12:35';
        $target_value = '2012-11-13 12:35';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDateTimeAndDateSameday()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15 12:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDateTimeAndDatePreviousDay()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-14';
        $target_value = '2012-11-15 12:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    //timestamp tests

    public function testValidateReturnsTrueForEqualDateTimeAndTimestamp()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15 14:58';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForEqualDateAndTimestamp()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForDifferentDateAndTimestamp()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-18';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForDifferentDateTimeAndTimestamp()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15 14:59';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForSameDateAndTimestamp()
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
}
