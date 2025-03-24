<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\ArtifactId;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
use Tuleap\CrossTracker\Query\ParametrizedWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class ArtifactIdFromWhereBuilder implements ValueWrapperVisitor
{
    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $parameters->comparison->getValueWrapper()->accept($this, $parameters);
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Artifact id metadata');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Artifact id metadata');
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $value = $value_wrapper->getValue();
        if ($value === '') {
            throw new LogicException('Comparison to empty string should have been flagged as invalid for the Artifact id metadata');
        }

        $tracker_ids_statement = EasyStatement::open()->in(
            'tracker.id IN (?*)',
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers)
        );

        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal => $this->getWhereForEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::NotEqual => $this->getWhereForNotEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::LesserThan => $this->getWhereForLesserThan((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::LesserThanOrEqual => $this->getWhereForLesserThanOrEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::GreaterThan => $this->getWhereForGreaterThan((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::GreaterThanOrEqual => $this->getWhereForGreaterThanOrEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::Between => throw new LogicException(
                'Between comparison expected a BetweenValueWrapper, not a SimpleValueWrapper'
            ),
            default => throw new LogicException('Other comparison types are invalid for Artifact id metadata'),
        };
    }

    private function getWhereForEqual(string $artifact_id, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        return new ParametrizedWhere(
            "$field_alias = ? AND $tracker_ids_statement",
            [$artifact_id, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForNotEqual(string $artifact_id, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        return new ParametrizedWhere(
            "$field_alias != ? AND $tracker_ids_statement",
            [$artifact_id, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForLesserThan(string $artifact_id, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        return new ParametrizedWhere(
            "$field_alias < ? AND $tracker_ids_statement",
            [$artifact_id, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForLesserThanOrEqual(string $artifact_id, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        return new ParametrizedWhere(
            "$field_alias <= ? AND $tracker_ids_statement",
            [$artifact_id, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForGreaterThan(string $artifact_id, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        return new ParametrizedWhere(
            "$field_alias > ? AND $tracker_ids_statement",
            [$artifact_id, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForGreaterThanOrEqual(string $artifact_id, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        return new ParametrizedWhere(
            "$field_alias >= ? AND $tracker_ids_statement",
            [$artifact_id, ...array_values($tracker_ids_statement->values())]
        );
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        $min_wrapper = $value_wrapper->getMinValue();
        assert($min_wrapper instanceof SimpleValueWrapper);
        $max_wrapper = $value_wrapper->getMaxValue();
        assert($max_wrapper instanceof SimpleValueWrapper);

        $tracker_ids_statement = EasyStatement::open()->in(
            'tracker.id IN (?*)',
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers)
        );

        return new ParametrizedWhere(
            "$parameters->field_alias BETWEEN ? AND ? AND $tracker_ids_statement",
            [$min_wrapper->getValue(), $max_wrapper->getValue(), ...array_values($tracker_ids_statement->values())]
        );
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with IN() should have been flagged as invalid for Artifact id metadata');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with current user should have been flagged as invalid for Artifact id metadata');
    }
}
