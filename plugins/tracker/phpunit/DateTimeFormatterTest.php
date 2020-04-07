<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_FormElement_DateTimeFormatterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $field;

    /** @var Tracker_FormElement_DateTimeFormatter */
    private $date_formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field          = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->date_formatter = new Tracker_FormElement_DateTimeFormatter($this->field);

        $user                 = Mockery::mock(PFUser::class);
        $user_manager         =  Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('getCurrentUser')->andReturn($user);
    }

    public function testItFormatsTimestampInRightFormat(): void
    {
        $timestamp = 1409752174;
        $expected  = '2014-09-03 15:49';

        $this->assertEquals($expected, $this->date_formatter->formatDate($timestamp));
    }

    public function testItFormatsTimestampInRightFormatForHoursBeforeNoon(): void
    {
        $timestamp = 1409708974;
        $expected  = '2014-09-03 03:49';

        $this->assertEquals($expected, $this->date_formatter->formatDate($timestamp));
    }

    public function testItValidatesWellFormedValue(): void
    {
        $value    = '2014-09-03 03:49';

        $this->assertTrue($this->date_formatter->validate($value));
    }

    public function testItDoesNotValidateNotWellFormedDate(): void
    {
        $value    = '2014/09/03 03:49';

        $this->field->shouldReceive('getLabel')->once();

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function testItDoesNotValidateNotWellFormedTime(): void
    {
        $value    = '2014-09-03 03-49-34';

        $this->field->shouldReceive('getLabel')->once();

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function testItDoesNotValidateDateIfNoSpaceBetweenDateAndTime(): void
    {
        $value    = '2014-09-0303:49';

        $this->field->shouldReceive('getLabel')->once();

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function testItDoesNotValidateDateIfNoTime(): void
    {
        $value    = '2014-09-03';

        $this->field->shouldReceive('getLabel')->once();

        $this->assertFalse($this->date_formatter->validate($value));
    }

    public function testItReturnsWellFormedDateForCSVWihoutSecondsEvenIfGiven(): void
    {
        $date_exploded = array(
            '2014',
            '09',
            '03',
            '08',
            '06',
            '12'
        );

        $expected = '2014-09-03 08:06';

        $this->assertEquals($expected, $this->date_formatter->getFieldDataForCSVPreview($date_exploded));
    }

    public function testItReturnsWellFormedDateForCSV(): void
    {
        $date_exploded = array(
            '2014',
            '09',
            '03',
            '08',
            '06'
        );

        $expected = '2014-09-03 08:06';

        $this->assertEquals($expected, $this->date_formatter->getFieldDataForCSVPreview($date_exploded));
    }
}
