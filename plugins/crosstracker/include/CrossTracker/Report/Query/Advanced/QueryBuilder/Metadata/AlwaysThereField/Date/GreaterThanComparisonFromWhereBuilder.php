<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;

use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;

class GreaterThanComparisonFromWhereBuilder extends FromWhereBuilder
{
    /**
     * @var DateTimeValueRounder
     */
    private $date_time_value_rounder;
    /**
     * @var string
     */
    private $alias_field;

    /**
     * @param DateTimeValueRounder $date_time_value_rounder
     * @param string $alias_field
     */
    public function __construct(DateTimeValueRounder $date_time_value_rounder, $alias_field)
    {
        $this->date_time_value_rounder = $date_time_value_rounder;
        $this->alias_field             = $alias_field;
    }

    /**
     * @param $value
     * @return ParametrizedFromWhere
     */
    protected function getParametrizedFromWhere($value)
    {
        $ceiled_timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);
        $where_parameters = [$ceiled_timestamp];
        $where            = "{$this->alias_field} > ?";

        return new ParametrizedFromWhere('', $where, [], $where_parameters);
    }
}
