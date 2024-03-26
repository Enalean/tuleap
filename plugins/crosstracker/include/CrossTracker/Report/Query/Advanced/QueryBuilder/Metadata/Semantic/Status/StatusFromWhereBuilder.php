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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedFromWhere>
 */
final class StatusFromWhereBuilder implements ValueWrapperVisitor
{
    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $parameters->comparison->getValueWrapper()->accept($this, $parameters);
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => $this->getFromWhereForEqual($parameters),
            ComparisonType::NotEqual => $this->getFromWhereForNotEqual($parameters),
            default                  => throw new LogicException('Other comparison types are invalid for Status semantic'),
        };
    }

    private function getFromWhereForEqual(MetadataValueWrapperParameters $parameters): ParametrizedFromWhere
    {
        $tracker_ids_statement = EasyStatement::open()->in(
            'field.tracker_id IN (?*)',
            array_map(
                static fn(Tracker $tracker) => $tracker->getId(),
                $parameters->trackers
            )
        );

        $from = <<<EOSQL
        LEFT JOIN (
            tracker_changeset_value AS changeset_value_status
            INNER JOIN (
                SELECT DISTINCT field_id
                FROM tracker_semantic_status AS field
                WHERE $tracker_ids_statement
            ) AS equal_status_field ON (equal_status_field.field_id = changeset_value_status.field_id)
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_status
                ON (
                    tracker_changeset_value_status.changeset_value_id = changeset_value_status.id
                )
        ) ON (
            changeset_value_status.changeset_id = tracker_artifact.last_changeset_id
        )
        EOSQL;

        $where = <<<EOSQL
        changeset_value_status.changeset_id IS NOT NULL
        AND tracker_changeset_value_status.bindvalue_id IN (
            SELECT open_value_id
            FROM tracker_semantic_status AS field
            WHERE $tracker_ids_statement
        )
        EOSQL;

        $values = $tracker_ids_statement->values();
        return new ParametrizedFromWhere($from, $where, $values, $values);
    }

    private function getFromWhereForNotEqual(MetadataValueWrapperParameters $parameters): ParametrizedFromWhere
    {
        $tracker_ids_statement = EasyStatement::open()->in(
            'field.tracker_id IN (?*)',
            array_map(
                static fn(Tracker $tracker) => $tracker->getId(),
                $parameters->trackers
            )
        );

        $from = <<<EOSQL
        LEFT JOIN (
            tracker_changeset_value AS not_equal_changeset_value_status
            INNER JOIN tracker_changeset_value_list AS not_equal_tracker_changeset_value_status
                ON (
                    not_equal_tracker_changeset_value_status.changeset_value_id = not_equal_changeset_value_status.id
                )
            INNER JOIN (
                SELECT DISTINCT field_id
                FROM tracker_semantic_status AS field
                WHERE $tracker_ids_statement
            ) AS not_equal_status_field
                ON (
                    not_equal_status_field.field_id = not_equal_changeset_value_status.field_id
                )
        ) ON (
            not_equal_changeset_value_status.changeset_id = tracker_artifact.last_changeset_id
        )
        EOSQL;

        $where = <<<EOSQL
        not_equal_changeset_value_status.changeset_id IS NOT NULL
        AND not_equal_tracker_changeset_value_status.bindvalue_id IN (
            SELECT static.id
            FROM tracker_field_list_bind_static_value AS static
                INNER JOIN tracker_field AS field
                    ON field.id = static.field_id AND $tracker_ids_statement
                INNER JOIN tracker_semantic_status AS SS1
                    ON field.id = SS1.field_id
                LEFT JOIN tracker_semantic_status AS SS2
                    ON static.field_id = SS2.field_id AND static.id = SS2.open_value_id
            WHERE SS2.open_value_id IS NULL
        )
        EOSQL;

        $values = $tracker_ids_statement->values();
        return new ParametrizedFromWhere($from, $where, $values, $values);
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Status semantic');
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to a simple value should have been flagged as invalid for Status semantic');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Status semantic');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Status semantic');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Status semantic');
    }
}
