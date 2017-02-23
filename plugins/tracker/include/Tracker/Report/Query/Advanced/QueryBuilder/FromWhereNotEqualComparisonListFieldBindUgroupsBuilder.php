<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Tracker\Report\Query\Advanced\FromWhere;

class FromWhereNotEqualComparisonListFieldBindUgroupsBuilder
{
    /**
     * @return FromWhere
     */
    public function getFromWhere(
        $field_id,
        $changeset_value_alias,
        $changeset_value_field_alias,
        $ugroup_alias,
        $bind_value_alias,
        $filter_alias,
        $tracker_id,
        $condition
    ) {
        $from = " LEFT JOIN (
            SELECT c.artifact_id AS artifact_id
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            LEFT JOIN (
              tracker_changeset_value AS $changeset_value_alias
              INNER JOIN tracker_changeset_value_list AS $changeset_value_field_alias ON (
                $changeset_value_field_alias.changeset_value_id = $changeset_value_alias.id
              )
              INNER JOIN tracker_field_list_bind_ugroups_value AS $bind_value_alias ON (
                $bind_value_alias.id = $changeset_value_field_alias.bindvalue_id
                AND $bind_value_alias.field_id = $changeset_value_alias.field_id
              )
              INNER JOIN ugroup AS $ugroup_alias ON (
                $bind_value_alias.ugroup_id = $ugroup_alias.ugroup_id
                AND $condition
              )
            ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = $field_id)
            WHERE artifact.tracker_id = $tracker_id AND ( $changeset_value_alias.changeset_id IS NOT NULL )
        ) AS $filter_alias ON (artifact.id = $filter_alias.artifact_id)";

        $where = "$filter_alias.artifact_id IS NULL";

        return new FromWhere($from, $where);
    }
}
