<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Numeric;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;

final class NumericSelectFromBuilder
{
    public function getSelectFrom(DuckTypedFieldSelect $field): IProvideParametrizedSelectAndFromSQLFragments
    {
        $suffix                      = md5($field->name);
        $suffix_int                  = "int_$suffix";
        $suffix_float                = "float_$suffix";
        $tracker_field_alias         = "TF_$suffix";
        $changeset_value_alias       = "CV_$suffix";
        $changeset_value_int_alias   = "CVInt_$suffix";
        $changeset_value_float_alias = "CVFloat_$suffix";

        $fields_id_statement = EasyStatement::open()->in(
            "$tracker_field_alias.id IN (?*)",
            $field->field_ids
        );
        $select              = "$changeset_value_int_alias.value AS $suffix_int, $changeset_value_float_alias.value AS $suffix_float";
        $from                = <<<EOSQL
        LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_int AS $changeset_value_int_alias
            ON $changeset_value_int_alias.changeset_value_id = $changeset_value_alias.id
         LEFT JOIN tracker_changeset_value_float AS $changeset_value_float_alias
            ON $changeset_value_float_alias.changeset_value_id = $changeset_value_alias.id
        EOSQL;

        return new ParametrizedSelectFrom(
            $select,
            $from,
            $fields_id_statement->values(),
        );
    }
}
