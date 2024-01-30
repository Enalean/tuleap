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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\CrossTracker\Report\Query\ParametrizedWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedAndFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final class NumericFromWhereBuilder
{
    public function getFromWhere(
        DuckTypedField $duck_typed_field,
        Comparison $comparison,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $suffix = spl_object_hash($comparison);

        $tracker_field_alias         = "TF_{$suffix}";
        $changeset_value_alias       = "CV_{$suffix}";
        $changeset_value_int_alias   = "CVInt_{$suffix}";
        $changeset_value_float_alias = "CVFloat_{$suffix}";

        $fields_id_statement = EasyStatement::open()->in(
            "$tracker_field_alias.id IN (?*)",
            $duck_typed_field->field_ids
        );
        $from                = <<<EOSQL
        LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND last_changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_int AS $changeset_value_int_alias
            ON $changeset_value_int_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_changeset_value_float AS $changeset_value_float_alias
            ON $changeset_value_float_alias.changeset_value_id = $changeset_value_alias.id
        EOSQL;
        $where               = "$tracker_field_alias.id IS NOT NULL";

        return new ParametrizedAndFromWhere(
            new ParametrizedFromWhere($from, $where, $fields_id_statement->values(), []),
            $this->getCondition($changeset_value_int_alias, $changeset_value_float_alias, $comparison)
        );
    }

    private function getCondition(
        string $changeset_value_int_alias,
        string $changeset_value_float_alias,
        Comparison $comparison,
    ): ParametrizedWhere {
        $wrapper = $comparison->getValueWrapper();
        assert($wrapper instanceof SimpleValueWrapper);
        $value = $wrapper->getValue();

        return match ($comparison->getType()) {
            ComparisonType::Equal => $this->getWhereForEqual(
                $changeset_value_int_alias,
                $changeset_value_float_alias,
                $wrapper,
            ),
            ComparisonType::NotEqual => $this->getWhereForNotEqual(
                $changeset_value_int_alias,
                $changeset_value_float_alias,
                $wrapper
            ),
            ComparisonType::LesserThan => new ParametrizedWhere(
                "$changeset_value_int_alias.value < ? OR $changeset_value_float_alias.value < ?",
                [$value, $value]
            ),
            ComparisonType::GreaterThan => new ParametrizedWhere(
                "$changeset_value_int_alias.value > ? OR $changeset_value_float_alias.value > ?",
                [$value, $value]
            ),
            ComparisonType::LesserThanOrEqual => throw new \LogicException(
                'Lesser Than Or Equal comparison for fields is not implemented yet'
            ),
            ComparisonType::GreaterThanOrEqual => new ParametrizedWhere(
                "$changeset_value_int_alias.value >= ? OR $changeset_value_float_alias.value >= ?",
                [$value, $value]
            ),
            ComparisonType::Between => throw new \LogicException(
                'Between comparison for fields is not implemented yet'
            ),
            default => throw new \LogicException('Other comparison types are invalid for Numeric field')
        };
    }

    private function getWhereForEqual(
        string $changeset_value_int_alias,
        string $changeset_value_float_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere("$changeset_value_int_alias.value IS NULL AND $changeset_value_float_alias.value IS NULL", []);
        }
        return new ParametrizedWhere("$changeset_value_int_alias.value = ? OR $changeset_value_float_alias.value = ?", [$value, $value]);
    }

    private function getWhereForNotEqual(
        string $changeset_value_int_alias,
        string $changeset_value_float_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere("$changeset_value_int_alias.value IS NOT NULL OR $changeset_value_float_alias.value IS NOT NULL", []);
        }

        $where = <<<EOSQL
        ($changeset_value_int_alias.value IS NULL OR $changeset_value_int_alias.value != ?)
        AND ($changeset_value_float_alias.value IS NULL OR $changeset_value_float_alias.value != ?)
        EOSQL;
        return new ParametrizedWhere($where, [$value, $value]);
    }
}
