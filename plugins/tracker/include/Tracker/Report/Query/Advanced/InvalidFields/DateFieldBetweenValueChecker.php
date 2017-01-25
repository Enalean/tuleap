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

use DateTime;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;

class DateFieldBetweenValueChecker implements InvalidFieldChecker, ValueWrapperVisitor
{
    /**
     * @var EmptyStringChecker
     */
    private $empty_string_checker;

    public function __construct(EmptyStringChecker $empty_string_checker)
    {
        $this->empty_string_checker = $empty_string_checker;
    }

    public function checkFieldIsValidForComparison(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $date_values = $comparison->getValueWrapper()->accept($this, new ValueWrapperParameters($field));

        foreach ($date_values as $value) {
            $date_value = DateTime::createFromFormat(DateFieldChecker::DATE_FORMAT, $value);

            if ($this->empty_string_checker->isEmptyStringAProblem($value)) {
                throw new DateToEmptyStringComparisonException($comparison, $field);
            }

            if ($date_value === false && $value !== '') {
                throw new DateToStringComparisonException($field, $value);
            }
        }
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $current_date_time = $value_wrapper->getValue();

        return $current_date_time->format(DateFieldChecker::DATE_FORMAT);
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $values = array();
        $values[] = $value_wrapper->getMinValue()->accept($this, $parameters);
        $values[] = $value_wrapper->getMaxValue()->accept($this, $parameters);

        return $values;
    }
}
