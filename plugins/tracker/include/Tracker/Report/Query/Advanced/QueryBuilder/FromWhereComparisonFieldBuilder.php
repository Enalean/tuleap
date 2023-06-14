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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final class FromWhereComparisonFieldBuilder
{
    public function getFromWhere(
        int $field_id,
        string $changeset_value_alias,
        string $changeset_value_field_alias,
        string $tracker_changeset_value_table,
        string|EasyStatement $condition,
        array $from_parameters,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $from = " LEFT JOIN (
            tracker_changeset_value AS $changeset_value_alias
            INNER JOIN $tracker_changeset_value_table AS $changeset_value_field_alias
             ON ($changeset_value_field_alias.changeset_value_id = $changeset_value_alias.id
                 AND $condition
             )
         ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = ?)";

        $where = "$changeset_value_alias.changeset_id IS NOT NULL";

        return new ParametrizedFromWhere($from, $where, [...$from_parameters, $field_id], []);
    }
}
