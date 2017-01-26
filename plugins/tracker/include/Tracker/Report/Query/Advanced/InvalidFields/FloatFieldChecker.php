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

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;

class FloatFieldChecker implements InvalidFieldChecker, ValueWrapperVisitor
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
        try {
            $value = $comparison->getValueWrapper()->accept($this, new ValueWrapperParameters($field));
        } catch (NowIsNotSupportedException $exception) {
            throw new FloatToNowComparisonException($field);
        }

        if ($this->empty_string_checker->isEmptyStringAProblem($value)) {
            throw new FloatToEmptyStringComparisonException($comparison, $field);
        }

        if (! is_numeric($value) && $value !== "") {
            throw new FloatToStringComparisonException($field, $value);
        }
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        throw new NowIsNotSupportedException();
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
    }
}
