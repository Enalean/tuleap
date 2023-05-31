<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_FormElement_Field_Date;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\CollectionOfDateValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;

final class DateTimeFieldCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var DateFieldChecker */
    private $date_field_checker;
    /** @var Tracker_FormElement_Field_Date */
    private $field;
    /** @var Comparison */
    private $comparison;

    protected function setUp(): void
    {
        $this->date_field_checker = new DateFieldChecker(
            new DateFormatValidator(new EmptyStringAllowed(), DateFormat::DATETIME),
            new CollectionOfDateValuesExtractor(DateFormat::DATETIME)
        );
        $this->field              = \Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->field->shouldReceive('getName')->andReturn('date field');
        $this->field->shouldReceive('isTimeDisplayed')->andReturn(true);
        $this->comparison = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison::class);
        $this->comparison->shouldReceive('acceptComparisonVisitor')->andReturn('');
    }

    public function testItDoesNotThrowWhenEmptyValueIsAllowed(): void
    {
        $value_wrapper = new SimpleValueWrapper('');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper)->once();

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItThrowsWhenEmptyValueIsForbidden(): void
    {
        $this->date_field_checker = new DateFieldChecker(
            new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME),
            new CollectionOfDateValuesExtractor(DateFormat::DATETIME)
        );
        $value_wrapper            = new SimpleValueWrapper('');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);
        $this->expectException(
            \Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToEmptyStringComparisonException::class
        );

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItDoesNotThrowForShortFormattedValue(): void
    {
        $value_wrapper = new SimpleValueWrapper('2014-11-14');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper)->once();

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItDoesNotThrowForLongFormattedValue(): void
    {
        $value_wrapper = new SimpleValueWrapper('2014-02-17 09:51');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper)->once();

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItThrowsForAnInvalidValue(): void
    {
        $value_wrapper = new SimpleValueWrapper('ittérativisme');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);
        $this->expectException(
            \Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringComparisonException::class
        );

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
