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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\UGroupList;

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
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, ParametrizedListFromWhere>
 */
final readonly class UGroupListFromWhereBuilder implements ValueWrapperVisitor
{
    private const OPENLIST_FROM = <<<EOSQL
        LEFT JOIN tracker_field_list_bind_ugroups_value AS tflbuv1 ON (
            tflbuv1.id = tcvol.bindvalue_id
        )
        LEFT JOIN ugroup AS ugroup1 ON (
            tflbuv1.ugroup_id = ugroup1.ugroup_id
        )
        EOSQL;
    private const LIST_FROM     = <<<EOSQL
        LEFT JOIN tracker_field_list_bind_ugroups_value AS tflbuv2 ON (
            tflbuv2.id = tcvl.bindvalue_id
        )
        LEFT JOIN ugroup AS ugroup2 ON (
            tflbuv2.ugroup_id = ugroup2.ugroup_id
        )
        EOSQL;

    public function __construct(
        private UgroupLabelConverter $label_converter,
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
            ComparisonType::NotIn    => throw new LogicException('In comparison expected a InValueWrapper, not a SimpleValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for UGroup List field')
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
                [Tracker_FormElement_Field_List::NONE_VALUE],
            );
        }

        if ($this->label_converter->isASupportedDynamicUgroup($value)) {
            $value = $this->label_converter->convertLabelToTranslationKey($value);
        }

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "ugroup1.name = ? OR ugroup2.name = ?",
            "$filter_alias.artifact_id IS NOT NULL",
            [$value, $value],
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
                [Tracker_FormElement_Field_List::NONE_VALUE],
            );
        }

        if ($this->label_converter->isASupportedDynamicUgroup($value)) {
            $value = $this->label_converter->convertLabelToTranslationKey($value);
        }

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "ugroup1.name = ? OR ugroup2.name = ?",
            "$filter_alias.artifact_id IS NULL",
            [$value, $value],
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
        $values            = array_map(function (SimpleValueWrapper $value_wrapper) {
            $value = $value_wrapper->getValue();
            if ($this->label_converter->isASupportedDynamicUgroup($value)) {
                $value = $this->label_converter->convertLabelToTranslationKey($value);
            }

            return $value;
        }, $wrapper->getValueWrappers());
        $ugroup1_statement = EasyStatement::open()->in("ugroup1.name IN (?*)", $values);
        $ugroup2_statement = EasyStatement::open()->in("ugroup2.name IN (?*)", $values);

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "$ugroup1_statement OR $ugroup2_statement",
            "$filter_alias.artifact_id IS NOT NULL",
            array_merge($ugroup1_statement->values(), $ugroup2_statement->values())
        );
    }

    private function getWhereForNotIn(
        string $filter_alias,
        InValueWrapper $wrapper,
    ): ParametrizedListFromWhere {
        $values            = array_map(function (SimpleValueWrapper $value_wrapper) {
            $value = $value_wrapper->getValue();
            if ($this->label_converter->isASupportedDynamicUgroup($value)) {
                $value = $this->label_converter->convertLabelToTranslationKey($value);
            }

            return $value;
        }, $wrapper->getValueWrappers());
        $ugroup1_statement = EasyStatement::open()->in("ugroup1.name IN (?*)", $values);
        $ugroup2_statement = EasyStatement::open()->in("ugroup2.name IN (?*)", $values);

        return new ParametrizedListFromWhere(
            self::OPENLIST_FROM,
            self::LIST_FROM,
            "$ugroup1_statement OR $ugroup2_statement",
            "$filter_alias.artifact_id IS NULL",
            array_merge($ugroup1_statement->values(), $ugroup2_statement->values())
        );
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for UGroup List fields');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for UGroup List fields');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for UGroup List fields');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for UGroup List fields');
    }
}
