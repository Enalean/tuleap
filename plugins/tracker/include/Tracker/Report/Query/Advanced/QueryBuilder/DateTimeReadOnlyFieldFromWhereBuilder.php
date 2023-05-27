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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FieldValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\MySelfIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\StatusOpenIsNotSupportedException;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

class DateTimeReadOnlyFieldFromWhereBuilder implements FieldFromWhereBuilder, ValueWrapperVisitor
{
    /**
     * @var FromWhereComparisonFieldReadOnlyBuilder
     */
    private $from_where_builder;

    /**
     * @var DateTimeReadOnlyConditionBuilder
     */
    private $condition_builder;

    public function __construct(
        FromWhereComparisonFieldReadOnlyBuilder $from_where_comparison_builder,
        DateTimeReadOnlyConditionBuilder $condition_builder,
    ) {
        $this->from_where_builder = $from_where_comparison_builder;
        $this->condition_builder  = $condition_builder;
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideFromAndWhereSQLFragments
    {
        $value     = $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($field));
        $condition = $this->condition_builder->getCondition($value);

        return $this->from_where_builder->getFromWhere($condition);
    }

    /**
     * @return string
     */
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    /**
     * @return string
     */
    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $field = $parameters->getField();
        if ($field->isTimeDisplayed() === true) {
            return $value_wrapper->getValue()->format(DateFormat::DATETIME);
        }
        return $value_wrapper->getValue()->format(DateFormat::DATE);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $values = [
            'min_value' => $value_wrapper->getMinValue()->accept($this, $parameters),
            'max_value' => $value_wrapper->getMaxValue()->accept($this, $parameters),
        ];

        return $values;
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, ValueWrapperParameters $parameters)
    {
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new MySelfIsNotSupportedException();
    }

    public function visitStatusOpenValueWrapper(
        StatusOpenValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new StatusOpenIsNotSupportedException();
    }
}
