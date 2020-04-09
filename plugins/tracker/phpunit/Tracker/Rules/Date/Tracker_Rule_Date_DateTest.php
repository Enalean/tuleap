<?php
/**
  * Copyright (c) Enalean, 2012-Present. All rights reserved
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Rule_Date_DateTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @var Tracker_Rule_Date
     */
    private $date_rule;



    protected function setUp(): void
    {
        $this->date_rule = new Tracker_Rule_Date();
    }

    /*
     * Source Field tests
     */
    public function testSetSourceFieldIdReturnsModelObject(): void
    {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEquals($this->date_rule, $set);
    }

    public function testGetSourceFieldIdReturnsFieldIdSet(): void
    {
        $this->date_rule->setSourceFieldId(45);
        $this->assertEquals(45, $this->date_rule->getSourceFieldId());
    }

    /*
     * Target Field tests
     */
    public function testSetTargetFieldIdReturnsModelObject(): void
    {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEquals($this->date_rule, $set);
    }

    public function testGetTargetFieldIdReturnsTargetIdSet(): void
    {
        $this->date_rule->setTargetFieldId(45);
        $this->assertEquals(45, $this->date_rule->getTargetFieldId());
    }

    /*
     * Tracker Field tests
     */
    public function testSetTrackerFieldIdReturnsModelObject(): void
    {
        $set = $this->date_rule->setTrackerId(123);
        $this->assertEquals($this->date_rule, $set);
    }

    public function testGetTrackerFieldIdReturnsTrackerIdSet(): void
    {
        $this->date_rule->setTrackerId(45);
        $this->assertEquals(45, $this->date_rule->getTrackerId());
    }

    /*
     * Comparator Field tests
     */
    public function testSetComparatorReturnsModelObject(): void
    {
        $set = $this->date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        $this->assertEquals($this->date_rule, $set);
    }

    public function testGetComparatorReturnsComparatorSet(): void
    {
        $this->date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);
        $this->assertEquals(Tracker_Rule_Date::COMPARATOR_EQUALS, $this->date_rule->getComparator());
    }

    public function testSetComparatorWillNotAllowRandomComparators(): void
    {
        $this->expectException(\Tracker_Rule_Date_InvalidComparatorException::class);
        $this->date_rule->setComparator('not a comparator');
    }

    public function testValidateReturnsTrueForTwoEqualDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForTwoUnequalDatesWithEquals(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2013-11-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForTwoUnequalDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2018-11-15';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsfalseForTwoUnequalDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2012-11-17';
        $target_value = '2012-11-16';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15';
        $target_value = '2014-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2012-11-19';
        $target_value = '2012-11-16';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterOrEqualDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15';
        $target_value = '2018-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2012-11-11';
        $target_value = '2012-11-14';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15';
        $target_value = '2015-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-19';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessOrEqualDates(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2016-12-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateThrowsAnExceptionWhenNoComparatorIsSet(): void
    {
        $this->expectException(\Tracker_Rule_Date_MissingComparatorException::class);
        $date_rule = new Tracker_Rule_Date();

        $source_value = '2015-12-15';
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForTwoEqualDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2012-11-15 14:58';
        $target_value = '2012-11-15 14:58';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForDateAndDateTime(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15 14:58';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForTwoUnequalDateTimesWithEquals(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2013-11-15 14:47';
        $target_value = '2013-11-15 14:48';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForUnequalDateTimeAndDateWithEquals(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = '2013-11-14';
        $target_value = '2013-11-15 14:48';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForTwoUnequalDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15 11:02';
        $target_value = '2012-11-15 11:03';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsfalseForTwoUnequalDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15 12:25';
        $target_value = '2012-11-15 12:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForDateTimeAndDateWithSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15 12:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2012-11-17 03:25';
        $target_value = '2012-11-16 14:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15 15:23';
        $target_value = '2014-11-15 14:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateThanDateTime(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15 15:23';
        $target_value = '2014-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateTimeThanDate(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2013-10-15';
        $target_value = '2014-11-15 14:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterDateTimeThanDateSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN);

        $source_value = '2014-11-15';
        $target_value = '2014-11-15 14:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2012-11-19 14:45';
        $target_value = '2012-11-16 23:02';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterOrEqualDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 10:25';
        $target_value = '2018-11-15 03:25';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForGreaterOrEqualDateTimesSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 03:25';
        $target_value = '2013-12-15 03:26';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDateTimesSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 10:25';
        $target_value = '2013-12-15 03:25';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForGreaterOrEqualDateTimesSameTime(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS);

        $source_value = '2013-12-15 10:25';
        $target_value = '2013-12-15 10:25';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2012-11-11 14:25';
        $target_value = '2012-11-14 14:25';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 14:35';
        $target_value = '2015-11-15 16:28';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessDateTimeSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 17:35';
        $target_value = '2018-12-15 16:28';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessDateTimeSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 17:35';
        $target_value = '2018-12-15 18:28';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForEqualDateTimeSameDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN);

        $source_value = '2018-12-15 17:35';
        $target_value = '2018-12-15 17:35';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15 12:35';
        $target_value = '2012-11-19 12:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForLessOrEqualDateTimes(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15 12:35';
        $target_value = '2012-11-13 12:35';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDateTimeAndDateSameday(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-15';
        $target_value = '2012-11-15 12:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForLessOrEqualDateTimeAndDatePreviousDay(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $source_value = '2012-11-14';
        $target_value = '2012-11-15 12:35';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    //timestamp tests

    public function testValidateReturnsTrueForEqualDateTimeAndTimestamp(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15 14:58';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsTrueForEqualDateAndTimestamp(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15';

        $this->assertTrue($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForDifferentDateAndTimestamp(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-18';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForDifferentDateTimeAndTimestamp(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15 14:59';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }

    public function testValidateReturnsFalseForSameDateAndTimestamp(): void
    {
        $date_rule = new Tracker_Rule_Date();
        $date_rule->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $source_value = strtotime('2012-11-15 14:58');
        $target_value = '2012-11-15';

        $this->assertFalse($date_rule->validate($source_value, $target_value));
    }
}
