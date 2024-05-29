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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Datetime;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Where\DuckTypedFieldWhere;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\FieldValueWrapperParameters;
use Tuleap\CrossTracker\Report\Query\ParametrizedWhere;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, ParametrizedWhere>
 */
final readonly class DatetimeFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(private DateTimeValueRounder $date_time_value_rounder)
    {
    }

    public function getFromWhere(
        DuckTypedFieldWhere $duck_typed_field,
        Comparison $comparison,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $suffix = spl_object_hash($comparison);

        $tracker_field_alias            = "TF_$suffix";
        $changeset_value_alias          = "CV_$suffix";
        $changeset_value_datetime_alias = $this->getAliasForDatetime($comparison);

        $fields_id_statement = EasyStatement::open()->in(
            "$tracker_field_alias.id IN (?*)",
            $duck_typed_field->field_ids
        );
        $from                = <<<EOSQL
        INNER JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND last_changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_date AS $changeset_value_datetime_alias
            ON $changeset_value_datetime_alias.changeset_value_id = $changeset_value_alias.id
        EOSQL;

        $where = $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($comparison));
        return new ParametrizedFromWhere(
            $from,
            $where->getWhere(),
            $fields_id_statement->values(),
            $where->getWhereParameters(),
        );
    }

    private function getAliasForDatetime(Comparison $comparison): string
    {
        $suffix = spl_object_hash($comparison);
        return "CVDatetime_$suffix";
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $comparison                     = $parameters->comparison;
        $changeset_value_datetime_alias = $this->getAliasForDatetime($comparison);

        return match ($comparison->getType()) {
            ComparisonType::Equal              => $this->getWhereForEqual($changeset_value_datetime_alias, $value_wrapper),
            ComparisonType::NotEqual           => $this->getWhereForNotEqual($changeset_value_datetime_alias, $value_wrapper),
            ComparisonType::LesserThan         => $this->getWhereForLesserThan($changeset_value_datetime_alias, $value_wrapper),
            ComparisonType::GreaterThan        => $this->getWhereForGreaterThan($changeset_value_datetime_alias, $value_wrapper),
            ComparisonType::LesserThanOrEqual  => $this->getWhereForLesserThanOrEqual($changeset_value_datetime_alias, $value_wrapper),
            ComparisonType::GreaterThanOrEqual => $this->getWhereForGreaterThanOrEqual($changeset_value_datetime_alias, $value_wrapper),
            ComparisonType::Between            => throw new LogicException(
                'Between comparison expected a BetweenValueWrapper, not a SimpleValueWrapper'
            ),
            default                            => throw new LogicException('Other comparison types are invalid for Date field')
        };
    }

    private function getWhereForEqual(
        string $changeset_value_datetime_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere("$changeset_value_datetime_alias.value IS NULL", []);
        }

        $min_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime((string) $value);
        $max_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime((string) $value);

        return new ParametrizedWhere(
            "$changeset_value_datetime_alias.value BETWEEN ? AND ?",
            [$min_value, $max_value]
        );
    }

    public function getWhereForNotEqual(
        string $changeset_value_datetime_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere("$changeset_value_datetime_alias.value IS NOT NULL", []);
        }

        $min_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime((string) $value);
        $max_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime((string) $value);

        return new ParametrizedWhere(
            "($changeset_value_datetime_alias.value IS NULL OR $changeset_value_datetime_alias.value NOT BETWEEN ? AND ?)",
            [$min_value, $max_value]
        );
    }

    private function getWhereForLesserThan(
        string $changeset_value_datetime_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        $limit_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime((string) $value);

        return new ParametrizedWhere(
            "$changeset_value_datetime_alias.value < ?",
            [$limit_value]
        );
    }

    private function getWhereForLesserThanOrEqual(
        string $changeset_value_datetime_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        $limit_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime((string) $value);

        return new ParametrizedWhere(
            "$changeset_value_datetime_alias.value <= ?",
            [$limit_value]
        );
    }

    private function getWhereForGreaterThan(
        string $changeset_value_datetime_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        $limit_value = $this->date_time_value_rounder->getCeiledTimestampFromDateTime((string) $value);

        return new ParametrizedWhere(
            "$changeset_value_datetime_alias.value > ?",
            [$limit_value]
        );
    }

    private function getWhereForGreaterThanOrEqual(
        string $changeset_value_datetime_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        $limit_value = $this->date_time_value_rounder->getFlooredTimestampFromDateTime((string) $value);

        return new ParametrizedWhere(
            "$changeset_value_datetime_alias.value >= ?",
            [$limit_value]
        );
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        $simple_value_wrapper = new SimpleValueWrapper($value_wrapper->getValue()->format(DateFormat::DATETIME));

        return $this->visitSimpleValueWrapper($simple_value_wrapper, $parameters);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        $comparison                     = $parameters->comparison;
        $changeset_value_datetime_alias = $this->getAliasForDatetime($comparison);

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

        return new ParametrizedWhere(
            "$changeset_value_datetime_alias.value BETWEEN ? AND ?",
            [$min_value, $max_value]
        );
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Datetime fields');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Datetime fields');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Datetime fields');
    }
}
