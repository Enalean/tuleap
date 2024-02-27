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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, void>
 */
final class TextFieldChecker implements InvalidFieldChecker, ValueWrapperVisitor
{
    /**
     * @throws FieldIsNotSupportedForComparisonException
     * @throws TextToMySelfComparisonException
     * @throws TextToNowComparisonException
     * @throws TextToStatusOpenComparisonException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkTextValueIsValid($comparison, $field),
            ComparisonType::Between => throw new FieldIsNotSupportedForComparisonException($field, 'between()'),
            ComparisonType::GreaterThan => throw new FieldIsNotSupportedForComparisonException($field, '>'),
            ComparisonType::GreaterThanOrEqual => throw new FieldIsNotSupportedForComparisonException($field, '>='),
            ComparisonType::LesserThan => throw new FieldIsNotSupportedForComparisonException($field, '<'),
            ComparisonType::LesserThanOrEqual => throw new FieldIsNotSupportedForComparisonException($field, '<='),
            ComparisonType::In => throw new FieldIsNotSupportedForComparisonException($field, 'in()'),
            ComparisonType::NotIn => throw new FieldIsNotSupportedForComparisonException($field, 'not in()'),
        };
    }

    /**
     * @throws TextToMySelfComparisonException
     * @throws TextToNowComparisonException
     * @throws TextToStatusOpenComparisonException
     */
    private function checkTextValueIsValid(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($field));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        // Do nothing, SimpleValue is valid
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new TextToNowComparisonException($parameters->field);
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new TextToMySelfComparisonException($parameters->field);
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new TextToStatusOpenComparisonException($parameters->field);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new \LogicException('Should not end there');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new \LogicException('Should not end there');
    }
}
