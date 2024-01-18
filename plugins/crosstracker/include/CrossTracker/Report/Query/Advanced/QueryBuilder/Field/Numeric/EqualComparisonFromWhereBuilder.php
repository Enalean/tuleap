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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Numeric;

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final class EqualComparisonFromWhereBuilder
{
    public function getFromWhere(DuckTypedField $duck_typed_field, Comparison $comparison): IProvideParametrizedFromAndWhereSQLFragments
    {
        $suffix  = spl_object_hash($comparison);
        $wrapper = $comparison->getValueWrapper();
        assert($wrapper instanceof SimpleValueWrapper);
        $value = $wrapper->getValue();

        $tracker_field_alias         = "TF_{$suffix}";
        $changeset_value_alias       = "CV_{$suffix}";
        $changeset_value_int_alias   = "CVInt_{$suffix}";
        $changeset_value_float_alias = "CVFloat_{$suffix}";

        $from            = <<<EOSQL
        LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $tracker_field_alias.name = ?)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND last_changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_int AS $changeset_value_int_alias
            ON $changeset_value_int_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_changeset_value_float AS $changeset_value_float_alias
            ON $changeset_value_float_alias.changeset_value_id = $changeset_value_alias.id
        EOSQL;
        $from_parameters = [$duck_typed_field->name];

        if ($value === '') {
            $where = "($changeset_value_int_alias.value IS NULL AND $changeset_value_float_alias.value IS NULL)";
            return new ParametrizedFromWhere($from, $where, $from_parameters, []);
        }

        $where            = "($changeset_value_int_alias.value = ? OR $changeset_value_float_alias.value = ?)";
        $where_parameters = [$value, $value];
        return new ParametrizedFromWhere($from, $where, $from_parameters, $where_parameters);
    }
}
