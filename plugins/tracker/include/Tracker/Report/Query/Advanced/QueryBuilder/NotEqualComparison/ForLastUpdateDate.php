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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotEqualComparison;

use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeReadOnlyConditionBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

final class ForLastUpdateDate implements DateTimeReadOnlyConditionBuilder
{
    public function __construct(private readonly DateTimeValueRounder $date_time_value_rounder)
    {
    }

    public function getCondition($value): ParametrizedSQLFragment
    {
        if ($value === '') {
            return new ParametrizedSQLFragment("1", []);
        }

        $floored_timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);
        $ceiled_timestamp  = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);
        $condition         = "c.submitted_on NOT BETWEEN ? AND ?";

        return new ParametrizedSQLFragment($condition, [$floored_timestamp, $ceiled_timestamp]);
    }
}
