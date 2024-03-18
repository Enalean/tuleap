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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\StaticList;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\FieldValueWrapperParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\ListFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\ParametrizedListFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, ParametrizedListFromWhere>
 */
final readonly class StaticListFromWhereBuilder implements ValueWrapperVisitor
{
    private const OPENLIST_FROM = <<<EOSQL
        LEFT JOIN tracker_field_list_bind_static_value AS tflbsv1 ON (
            tflbsv1.id = tcvol.bindvalue_id
        )
        LEFT JOIN tracker_field_openlist_value AS tfov ON (
            tfov.id = tcvol.openvalue_id
        )
        EOSQL;
    private const LIST_FROM     = <<<EOSQL
        LEFT JOIN tracker_field_list_bind_static_value AS tflbsv2 ON (
            tflbsv2.id = tcvl.bindvalue_id
        )
        EOSQL;

    public function __construct(
        private ListFromWhereBuilder $list_builder,
    ) {
    }

    public function getFromWhere(
        DuckTypedField $duck_typed_field,
        Comparison $comparison,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $suffix              = spl_object_hash($comparison);
        $tracker_field_alias = "TF_$suffix";
        $filter_alias        = $this->getAliasForFilter($comparison);

        $bind_from_where = $comparison->getValueWrapper()->accept(
            $this,
            new FieldValueWrapperParameters($comparison)
        );
        return $this->list_builder->getComposedFromWhere(
            $duck_typed_field,
            $tracker_field_alias,
            $filter_alias,
            $bind_from_where
        );
    }

    private function getAliasForFilter(Comparison $comparison): string
    {
        $suffix = spl_object_hash($comparison);
        return "FA_$suffix";
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $comparison   = $parameters->comparison;
        $filter_alias = $this->getAliasForFilter($comparison);

        return match ($comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual($filter_alias, $value_wrapper),
            ComparisonType::NotEqual => $this->getWhereForNotEqual($filter_alias, $value_wrapper),
            ComparisonType::In,
            ComparisonType::NotIn    => throw new LogicException(
                'In comparison expected a InValueWrapper, not a SimpleValueWrapper'
            ),
            default                  => throw new LogicException('Other comparison types are invalid for Static List field')
        };
    }

    private function getWhereForEqual(
        string $filter_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedListFromWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedListFromWhere(
                self::OPENLIST_FROM,
                self::LIST_FROM,
                "IF(tcvl.bindvalue_id IS NOT NULL, tcvl.bindvalue_id = ?, tcvol.changeset_value_id IS NULL)",
                "$filter_alias.artifact_id IS NOT NULL",
                [Tracker_FormElement_Field_List::NONE_VALUE]
            );
        }

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "tflbsv1.label = ? OR tfov.label = ? OR tflbsv2.label = ?",
            "$filter_alias.artifact_id IS NOT NULL",
            [$value, $value, $value]
        );
    }

    private function getWhereForNotEqual(
        string $filter_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedListFromWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedListFromWhere(
                self::OPENLIST_FROM,
                self::LIST_FROM,
                "IF(tcvl.bindvalue_id IS NOT NULL, tcvl.bindvalue_id = ?, tcvol.changeset_value_id IS NULL)",
                "$filter_alias.artifact_id IS NULL",
                [Tracker_FormElement_Field_List::NONE_VALUE]
            );
        }

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "tflbsv1.label = ? OR tfov.label = ? OR tflbsv2.label = ?",
            "$filter_alias.artifact_id IS NULL",
            [$value, $value, $value]
        );
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        $comparison   = $parameters->comparison;
        $filter_alias = $this->getAliasForFilter($comparison);

        return match ($comparison->getType()) {
            ComparisonType::In       => $this->getWhereForIn($filter_alias, $collection_of_value_wrappers),
            ComparisonType::NotIn    => $this->getWhereForNotIn($filter_alias, $collection_of_value_wrappers),
            ComparisonType::Equal    => throw new LogicException('Equal comparison expected a SimpleValueWrapper, not a InValueWrapper'),
            ComparisonType::NotEqual => throw new LogicException('Not Equal comparison expected a SimpleValueWrapper, not a InValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for Static List field')
        };
    }

    private function getWhereForIn(
        string $filter_alias,
        InValueWrapper $wrapper,
    ): ParametrizedListFromWhere {
        $values            = array_map(static fn(SimpleValueWrapper $value_wrapper) => $value_wrapper->getValue(), $wrapper->getValueWrappers());
        $tflbsv1_statement = EasyStatement::open()->in("tflbsv1.label IN (?*)", $values);
        $tflbsv2_statement = EasyStatement::open()->in("tflbsv2.label IN (?*)", $values);
        $tfov_statement    = EasyStatement::open()->in("tfov.label IN (?*)", $values);

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "$tflbsv1_statement OR $tflbsv2_statement OR $tfov_statement",
            "$filter_alias.artifact_id IS NOT NULL",
            array_merge(
                $tflbsv1_statement->values(),
                $tflbsv2_statement->values(),
                $tfov_statement->values(),
            ),
        );
    }

    private function getWhereForNotIn(
        string $filter_alias,
        InValueWrapper $wrapper,
    ): ParametrizedListFromWhere {
        $values            = array_map(static fn(SimpleValueWrapper $value_wrapper) => $value_wrapper->getValue(), $wrapper->getValueWrappers());
        $tflbsv1_statement = EasyStatement::open()->in("tflbsv1.label IN (?*)", $values);
        $tflbsv2_statement = EasyStatement::open()->in("tflbsv2.label IN (?*)", $values);
        $tfov_statement    = EasyStatement::open()->in("tfov.label IN (?*)", $values);

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "$tflbsv1_statement OR $tflbsv2_statement OR $tfov_statement",
            "$filter_alias.artifact_id IS NULL",
            array_merge(
                $tflbsv1_statement->values(),
                $tflbsv2_statement->values(),
                $tfov_statement->values(),
            ),
        );
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Static List fields');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Static List fields');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Static List fields');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Static List fields');
    }
}
