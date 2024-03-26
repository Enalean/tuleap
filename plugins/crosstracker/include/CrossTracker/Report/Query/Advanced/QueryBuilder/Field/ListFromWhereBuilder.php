<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final readonly class ListFromWhereBuilder
{
    public function getComposedFromWhere(
        DuckTypedField $duck_typed_field,
        string $tracker_field_alias,
        string $filter_alias,
        ParametrizedListFromWhere $bind_from_where,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $fields_id_statement        = EasyStatement::open()->in(
            "$tracker_field_alias.id IN(?*)",
            $duck_typed_field->field_ids
        );
        $filter_field_ids_statement = EasyStatement::open()->in(
            'tcv.field_id IN(?*)',
            $duck_typed_field->field_ids
        );

        $from = <<<EOSQL
        INNER JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN (
            SELECT c.artifact_id AS artifact_id
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            INNER JOIN (
                tracker_changeset_value AS tcv
                LEFT JOIN (tracker_changeset_value_openlist AS tcvol
                    {$bind_from_where->openlist_from}
                ) ON (tcvol.changeset_value_id = tcv.id)
                LEFT JOIN (tracker_changeset_value_list AS tcvl
                    {$bind_from_where->list_from}
                ) ON (tcvl.changeset_value_id = tcv.id)
            ) ON (
                tcv.changeset_id = c.id AND $filter_field_ids_statement
                AND ({$bind_from_where->filter_where})
            )
        ) AS $filter_alias ON (tracker_artifact.id = $filter_alias.artifact_id)
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            $bind_from_where->where,
            array_merge(
                $fields_id_statement->values(),
                $filter_field_ids_statement->values(),
                $bind_from_where->filter_where_parameters,
            ),
            []
        );
    }
}
