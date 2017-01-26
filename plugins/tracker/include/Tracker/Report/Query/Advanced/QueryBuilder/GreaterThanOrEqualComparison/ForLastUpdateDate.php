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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\GreaterThanOrEqualComparison;

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
        $value = $comparison->getValueWrapper()->accept($this, new ValueWrapperParameters($field));

        $floored_timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);
        $floored_timestamp = $this->escapeInt($floored_timestamp);
        $condition         = "c.submitted_on >= $floored_timestamp";

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

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
