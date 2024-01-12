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
        $wrapper = $comparison->getValueWrapper();
        assert($wrapper instanceof SimpleValueWrapper);
        $value = $wrapper->getValue();

        $from = <<<'EOSQL'
        LEFT JOIN tracker_field ON tracker.id = tracker_field.tracker_id AND tracker_field.name = ?
        LEFT JOIN tracker_changeset_value AS CV1 ON (tracker_field.id = CV1.field_id AND last_changeset.id = CV1.changeset_id)
        LEFT JOIN tracker_changeset_value_int AS TCVI1 ON (TCVI1.changeset_value_id = CV1.id)
        LEFT JOIN tracker_changeset_value_float AS TCVF1 ON (TCVF1.changeset_value_id = CV1.id)
        EOSQL;

        $from_parameters  = [$duck_typed_field->name];
        $where            = '(TCVI1.value = ? OR TCVF1.value = ?)';
        $where_parameters = [$value, $value];

        return new ParametrizedFromWhere($from, $where, $from_parameters, $where_parameters);
    }
}
