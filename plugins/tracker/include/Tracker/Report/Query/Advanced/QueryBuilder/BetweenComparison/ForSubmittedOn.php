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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\BetweenComparison;

use CodendiDataAccess;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeReadOnlyConditionBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;

final class ForSubmittedOn implements DateTimeReadOnlyConditionBuilder
{
    /**
     * @var DateTimeValueRounder
     */
    private $date_time_value_rounder;

    public function __construct(DateTimeValueRounder $date_time_value_rounder)
    {
        $this->date_time_value_rounder = $date_time_value_rounder;
    }

    public function getCondition($value)
    {
        $min_value = $value['min_value'];
        $max_value = $value['max_value'];

        $min_value_floored_timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($min_value);
        $max_value_ceiled_timestamp  = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($max_value);

        $min_value_floored_timestamp = $this->escapeInt($min_value_floored_timestamp);
        $max_value_ceiled_timestamp  = $this->escapeInt($max_value_ceiled_timestamp);

        $condition = "artifact.submitted_on >= $min_value_floored_timestamp
            AND artifact.submitted_on <= $max_value_ceiled_timestamp";

        return $condition;
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
