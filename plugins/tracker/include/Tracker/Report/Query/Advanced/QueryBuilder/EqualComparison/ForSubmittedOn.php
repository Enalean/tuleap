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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;

class ForSubmittedOn implements FromWhereBuilder
{
    /**
     * @var DateTimeConditionBuilder
     */
    private $date_time_condition_builder;

    public function __construct(DateTimeConditionBuilder $date_time_condition_builder)
    {
        $this->date_time_condition_builder = $date_time_condition_builder;
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $value = $comparison->getValueWrapper()->getValue();

        if ($value === '') {
            $condition = "1";
        } else {
            $condition = "artifact.submitted_on " . $this->date_time_condition_builder->buildConditionForDateOrDateTime($value);
        }

        $from  = "";
        $where = "$condition";

        return new FromWhere($from, $where);
    }
}
