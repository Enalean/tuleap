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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
use Tuleap\CrossTracker\Query\ParametrizedWhere;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class DateFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(
        private DateTimeValueRounder $date_time_value_rounder,
    ) {
    }

    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $parameters->comparison->getValueWrapper()->accept($this, $parameters);
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $value = $value_wrapper->getValue();
        if ($value === '') {
            throw new LogicException('Comparison to empty string should have been flagged as invalid for Date metadata');
        }

        $tracker_ids_statement = EasyStatement::open()->in(
            'tracker.id IN (?*)',
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers)
        );

        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal              => $this->getWhereForEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::NotEqual           => $this->getWhereForNotEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::LesserThan         => $this->getWhereForLesserThan((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::GreaterThan        => $this->getWhereForGreaterThan((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::LesserThanOrEqual  => $this->getWhereForLesserThanOrEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::GreaterThanOrEqual => $this->getWhereForGreaterThanOrEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::Between            => throw new LogicException(
                'Between comparison expected a BetweenValueWrapper, not a SimpleValueWrapper'
            ),
            default                            => throw new LogicException('Other comparison types are invalid for Date metadata')
        };
    }

    private function getWhereForEqual(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $min_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);
        $max_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);

        return new ParametrizedWhere(
            "$field_alias BETWEEN ? AND ? AND $tracker_ids_statement",
            [$min_value, $max_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForNotEqual(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $min_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);
        $max_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);

        return new ParametrizedWhere(
            "$field_alias NOT BETWEEN ? AND ? AND $tracker_ids_statement",
            [$min_value, $max_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForLesserThan(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $limit_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);

        return new ParametrizedWhere(
            "$field_alias < ? AND $tracker_ids_statement",
            [$limit_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForGreaterThan(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $limit_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);

        return new ParametrizedWhere(
            "$field_alias > ? AND $tracker_ids_statement",
            [$limit_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForLesserThanOrEqual(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $limit_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);

        return new ParametrizedWhere(
            "$field_alias <= ? AND $tracker_ids_statement",
            [$limit_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForGreaterThanOrEqual(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $limit_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime($value);

        return new ParametrizedWhere(
            "$field_alias >= ? AND $tracker_ids_statement",
            [$limit_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        $simple_value_wrapper = new SimpleValueWrapper($value_wrapper->getValue()->format(DateFormat::DATETIME));

        return $this->visitSimpleValueWrapper($simple_value_wrapper, $parameters);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        $min_wrapper = $value_wrapper->getMinValue();
        if ($min_wrapper instanceof CurrentDateTimeValueWrapper) {
            $min_wrapper = new SimpleValueWrapper($min_wrapper->getValue()->format(DateFormat::DATETIME));
        }
        assert($min_wrapper instanceof SimpleValueWrapper);
        $min_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime((string) $min_wrapper->getValue());

        $max_wrapper = $value_wrapper->getMaxValue();
        if ($max_wrapper instanceof CurrentDateTimeValueWrapper) {
            $max_wrapper = new SimpleValueWrapper($max_wrapper->getValue()->format(DateFormat::DATETIME));
        }
        assert($max_wrapper instanceof SimpleValueWrapper);
        $max_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime((string) $max_wrapper->getValue());

        $tracker_ids_statement = EasyStatement::open()->in(
            'tracker.id IN (?*)',
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers)
        );

        return new ParametrizedWhere(
            "$parameters->field_alias BETWEEN ? AND ? AND $tracker_ids_statement",
            [$min_value, $max_value, ...array_values($tracker_ids_statement->values())]
        );
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Date semantic');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Date semantic');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Date semantic');
    }
}
