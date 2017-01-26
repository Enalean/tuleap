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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\BetweenComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateTimeFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonFieldReadOnlyBuilder;

class ForLastUpdateDate implements FromWhereBuilder, ValueWrapperVisitor
{
    /**
     * @var DateTimeValueRounder
     */
    private $date_time_value_rounder;
    /**
     * @var FromWhereComparisonFieldReadOnlyBuilder
     */
    private $from_where_builder;

    public function __construct(
        DateTimeValueRounder $date_time_value_rounder,
        FromWhereComparisonFieldReadOnlyBuilder $from_where_comparison_builder
    ) {
        $this->date_time_value_rounder = $date_time_value_rounder;
        $this->from_where_builder      = $from_where_comparison_builder;
    }

    /**
     * @return FromWhere
     */
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $comparison_value = $comparison->getValueWrapper()->accept($this, new ValueWrapperParameters($field));
        $min_value        = $comparison_value['min_value'];
        $max_value        = $comparison_value['max_value'];

        $min_value_floored_timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($min_value);
        $min_value_floored_timestamp = $this->escapeInt($min_value_floored_timestamp);

        $max_value_ceiled_timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($max_value);
        $max_value_ceiled_timestamp = $this->escapeInt($max_value_ceiled_timestamp);

        $condition = "c.submitted_on >= $min_value_floored_timestamp AND c.submitted_on <= $max_value_ceiled_timestamp";

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
        return $value_wrapper->getValue()->format(DateTimeFieldChecker::DATETIME_FORMAT);
    }

    /**
     * @return array
     */
    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $values = array(
            'min_value' => $value_wrapper->getMinValue()->accept($this, $parameters),
            'max_value' => $value_wrapper->getMaxValue()->accept($this, $parameters)
        );

        return $values;
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
