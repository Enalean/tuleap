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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final class FromWhereComparisonListFieldBindUgroupsBuilder
{
    public function getFromWhere(QueryListFieldPresenter $query_presenter): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from = " LEFT JOIN (
            tracker_changeset_value AS $query_presenter->changeset_value_alias
            INNER JOIN tracker_changeset_value_list AS $query_presenter->changeset_value_list_alias
             ON ($query_presenter->changeset_value_list_alias.changeset_value_id = $query_presenter->changeset_value_alias.id)
            INNER JOIN tracker_field_list_bind_ugroups_value AS $query_presenter->bind_value_alias
             ON (
                $query_presenter->bind_value_alias.id = $query_presenter->changeset_value_list_alias.bindvalue_id
                AND $query_presenter->bind_value_alias.field_id = $query_presenter->changeset_value_alias.field_id
             )
            INNER JOIN ugroup AS $query_presenter->list_value_alias
             ON (
                $query_presenter->bind_value_alias.ugroup_id = $query_presenter->list_value_alias.ugroup_id
                AND $query_presenter->condition
             )
         ) ON ($query_presenter->changeset_value_alias.changeset_id = c.id AND $query_presenter->changeset_value_alias.field_id = ?)";

        $where = "$query_presenter->changeset_value_alias.changeset_id IS NOT NULL";

        return new ParametrizedFromWhere($from, $where, [...$query_presenter->parameters, $query_presenter->field_id], []);
    }
}
