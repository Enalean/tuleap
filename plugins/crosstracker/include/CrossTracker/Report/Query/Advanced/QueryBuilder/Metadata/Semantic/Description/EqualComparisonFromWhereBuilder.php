<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;

use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\DB\DBFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class EqualComparisonFromWhereBuilder extends DescriptionFromWhereBuilder
{
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        $where_parameters = [];
        $value            = $comparison->getValueWrapper()->getValue();

        if ($value === '') {
            $matches_value = " = ''";
        } else {
            $matches_value      = " LIKE ?";
            $where_parameters[] = '%' . DBFactory::getMainTuleapDBConnection()->getDB()->escapeLikeValue($value) . '%';
        }

        $from  = $this->getFrom();
        $where = "changeset_value_description.changeset_id IS NOT NULL
            AND tracker_changeset_value_description.value $matches_value";

        return new ParametrizedFromWhere($from, $where, [], $where_parameters);
    }
}
