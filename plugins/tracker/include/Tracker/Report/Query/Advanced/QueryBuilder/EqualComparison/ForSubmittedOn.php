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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison;

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
        if ($value === '') {
            $condition = "0";
        } else {
            $floored_timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);
            $ceiled_timestamp  = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);
            $floored_timestamp = $this->escapeInt($floored_timestamp);
            $ceiled_timestamp  = $this->escapeInt($ceiled_timestamp);
            $condition         = "artifact.submitted_on BETWEEN $floored_timestamp AND $ceiled_timestamp";
        }

        return $condition;
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
