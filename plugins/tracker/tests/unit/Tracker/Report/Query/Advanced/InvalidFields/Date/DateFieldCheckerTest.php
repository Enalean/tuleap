<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;

final class DateFieldCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_NAME = 'date_field';
    private Comparison $comparison;
    private \Tracker_FormElement_Field_Date $field;

    protected function setUp(): void
    {
        $this->field = TrackerFormElementDateFieldBuilder::aDateField(941)->withName(self::FIELD_NAME)->build();
    }

    /**
     * @throws DateToEmptyStringTermException
     * @throws DateToMySelfComparisonException
     * @throws DateToStatusOpenComparisonException
     * @throws DateToStringComparisonException
     * @throws FieldIsNotSupportedForComparisonException
     */
    private function check(): void
    {
        $checker = new DateFieldChecker();
        $checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItDoesNotThrowWhenEmptyValueIsAllowed(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(''));
        $this->check();
        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsWhenEmptyValueIsForbidden(): void
    {
        $this->comparison = new GreaterThanComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(''));
        $this->expectException(DateToEmptyStringTermException::class);
        $this->check();
    }

    public function testItAllowsShortFormattedValueForDateField(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('2014-11-14'));
        $this->check();
        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsLongFormattedValueForDateField(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('2014-02-17 09:51'));
        $this->expectException(DateToStringComparisonException::class);
        $this->check();
    }

    public function testItRejectsStringValue(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('ittérativisme'));
        $this->expectException(DateToStringComparisonException::class);
        $this->check();
    }

    public function testItAllowsShortFormattedValueForDateTimeField(): void
    {
        $this->field      = TrackerFormElementDateFieldBuilder::aDateField(140)
            ->withTime()
            ->withName(self::FIELD_NAME)
            ->build();
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('2014-11-14'));
        $this->check();
        $this->expectNotToPerformAssertions();
    }

    public function testItAllowsLongFormattedValueForDateTimeField(): void
    {
        $this->field      = TrackerFormElementDateFieldBuilder::aDateField(140)
            ->withTime()
            ->withName(self::FIELD_NAME)
            ->build();
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('2014-02-17 09:51'));
        $this->check();
        $this->expectNotToPerformAssertions();
    }
}
