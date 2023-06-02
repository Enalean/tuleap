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
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\MySelfIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\StatusOpenIsNotSupportedException;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, string | int | float | array{min_value: string | int | float, max_value: string | int | float}>
 */
final class DateTimeFieldFromWhereBuilder implements FieldFromWhereBuilder, ValueWrapperVisitor
{
    public function __construct(
        private readonly FromWhereComparisonFieldBuilder $from_where_builder,
        private readonly DateTimeConditionBuilder $condition_builder,
    ) {
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideFromAndWhereSQLFragments
    {
        $suffix   = spl_object_hash($comparison);
        $value    = $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($field));
        $field_id = (int) $field->getId();

        $changeset_value_date_alias = "CVDate_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";

        $condition = $this->condition_builder->getCondition($value, $changeset_value_date_alias);

        return $this->from_where_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_date_alias,
            'tracker_changeset_value_date',
            $condition
        );
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        if (! ($parameters->field instanceof \Tracker_FormElement_Field_Date)) {
            throw new \Exception("Field is not a date");
        }

        if ($parameters->field->isTimeDisplayed() === true) {
            return $value_wrapper->getValue()->format(DateFormat::DATETIME);
        }

        return $value_wrapper->getValue()->format(DateFormat::DATE);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        $min = $value_wrapper->getMinValue()->accept($this, $parameters);
        if (is_array($min)) {
            throw new \Exception("Unsupported between value");
        }

        $max = $value_wrapper->getMaxValue()->accept($this, $parameters);
        if (is_array($max)) {
            throw new \Exception("Unsupported between value");
        }

        return [
            'min_value' => $min,
            'max_value' => $max,
        ];
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new InIsNotSupportedException();
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new MySelfIsNotSupportedException();
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new StatusOpenIsNotSupportedException();
    }
}
