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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\Advanced\FromWhere;

class FromWhereNotEqualComparisonListFieldBuilder
{
    /**
     * @return FromWhere
     */
    public function getFromWhere(
        $field_id,
        $changeset_value_alias,
        $changeset_value_field_alias,
        $tracker_changeset_value_table,
        $list_value_table,
        $list_value_alias,
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
              INNER JOIN $tracker_changeset_value_table AS $changeset_value_field_alias ON (
                $changeset_value_field_alias.changeset_value_id = $changeset_value_alias.id
              )
              INNER JOIN $list_value_table AS $list_value_alias ON (
                $changeset_value_field_alias.bindvalue_id = $list_value_alias.id
                AND $condition
              )
            ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = $field_id)
            WHERE artifact.tracker_id = $tracker_id AND ( $changeset_value_alias.changeset_id IS NOT NULL )
        ) AS $filter_alias ON (artifact.id = $filter_alias.artifact_id)";

        $where = "$filter_alias.artifact_id IS NULL";

        return new FromWhere($from, $where);
    }
}
