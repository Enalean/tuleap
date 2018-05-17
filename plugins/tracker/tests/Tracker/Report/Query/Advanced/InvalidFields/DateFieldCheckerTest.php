<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tracker_FormElement_Field_Date;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\CollectionOfDateValuesExtractor;
use TuleapTestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;

require_once __DIR__.'/../../../../../bootstrap.php';

class DateFieldCheckerTest extends TuleapTestCase
{
    /** @var DateTimeFieldChecker */
    private $date_field_checker;
    /** @var Tracker_FormElement_Field_Date */
    private $field;
    /** @var Comparison */
    private $comparison;

    public function setUp()
    {
        parent::setUp();

        $this->date_field_checker = new DateFieldChecker(
            new DateFormatValidator(new EmptyStringAllowed(), DateFormat::DATE),
            new CollectionOfDateValuesExtractor(DateFormat::DATE)
        );
        $this->field              = aMockDateWithoutTimeField()->build();
        $this->comparison         = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
    }

    public function itDoesNotThrowWhenEmptyValueIsAllowed()
    {
        $value_wrapper = new SimpleValueWrapper('');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itThrowsWhenEmptyValueIsForbidden()
    {
        $this->date_field_checker = new DateFieldChecker(
            new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATE),
            new CollectionOfDateValuesExtractor(DateFormat::DATE)
        );
        $value_wrapper = new SimpleValueWrapper('');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);
        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToEmptyStringComparisonException');

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function itDoesNotThrowForShortFormattedValue()
    {
        $value_wrapper = new SimpleValueWrapper('2014-11-14');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itThrowsForAnInvalidValue()
    {
        $value_wrapper = new SimpleValueWrapper('ittérativisme');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);
        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringComparisonException');

        $this->date_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
