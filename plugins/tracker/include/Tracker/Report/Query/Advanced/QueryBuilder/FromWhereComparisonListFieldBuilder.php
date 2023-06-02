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

use Tuleap\Tracker\Report\Query\FromWhere;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

final class FromWhereComparisonListFieldBuilder
{
    public function getFromWhere(QueryListFieldPresenter $query_presenter): IProvideFromAndWhereSQLFragments
    {
        $from = " LEFT JOIN (
            tracker_changeset_value AS $query_presenter->changeset_value_alias
            INNER JOIN $query_presenter->tracker_changeset_value_table AS $query_presenter->changeset_value_list_alias
             ON ($query_presenter->changeset_value_list_alias.changeset_value_id = $query_presenter->changeset_value_alias.id)
            INNER JOIN $query_presenter->list_value_table AS $query_presenter->list_value_alias
             ON (
                $query_presenter->condition
             )
         ) ON ($query_presenter->changeset_value_alias.changeset_id = c.id AND $query_presenter->changeset_value_alias.field_id = $query_presenter->field_id)";

        $where = "$query_presenter->changeset_value_alias.changeset_id IS NOT NULL";

        return new FromWhere($from, $where);
    }
}
