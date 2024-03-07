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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\UserList;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\FieldValueWrapperParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\ListFromWhereBuilder;
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
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, ParametrizedFromWhere>
 */
final readonly class UserListFromWhereBuilder implements ValueWrapperVisitor
{
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
            ComparisonType::NotIn    => throw new LogicException('In comparison expected a InValueWrapper, not a SimpleValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for UGroup List field')
        };
    }

    private function getWhereForEqual(
        string $filter_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedFromWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            $from = <<<EOSQL
            tracker_changeset_value as tcv
            INNER JOIN tracker_changeset_value_list as tcvl ON (
                tcvl.changeset_value_id = tcv.id AND tcvl.bindvalue_id = ?
            )
            EOSQL;

            return new ParametrizedFromWhere(
                $from,
                "$filter_alias.artifact_id IS NOT NULL",
                [Tracker_FormElement_Field_List::NONE_VALUE],
                []
            );
        }

        $from = <<<EOSQL
        tracker_changeset_value AS tcv
        INNER JOIN tracker_changeset_value_list AS tcvl ON (
            tcvl.changeset_value_id = tcv.id
        )
        INNER JOIN user ON (
            user.user_id = tcvl.bindvalue_id AND user.user_name = ?
        )
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            "$filter_alias.artifact_id IS NOT NULL",
            [$value],
            []
        );
    }

    private function getWhereForNotEqual(
        string $filter_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedFromWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            $from = <<<EOSQL
            tracker_changeset_value as tcv
            INNER JOIN tracker_changeset_value_list as tcvl ON (
                tcvl.changeset_value_id = tcv.id AND tcvl.bindvalue_id = ?
            )
            EOSQL;

            return new ParametrizedFromWhere(
                $from,
                "$filter_alias.artifact_id IS NULL",
                [Tracker_FormElement_Field_List::NONE_VALUE],
                []
            );
        }

        $from = <<<EOSQL
        tracker_changeset_value AS tcv
        INNER JOIN tracker_changeset_value_list AS tcvl ON (
            tcvl.changeset_value_id = tcv.id
        )
        INNER JOIN user ON (
            user.user_id = tcvl.bindvalue_id AND user.user_name = ?
        )
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            "$filter_alias.artifact_id IS NULL",
            [$value],
            []
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
    ): ParametrizedFromWhere {
        $values_statement = EasyStatement::open()->in(
            "user.user_name IN (?*)",
            array_map(static fn(SimpleValueWrapper $value_wrapper) => $value_wrapper->getValue(), $wrapper->getValueWrappers())
        );

        $from = <<<EOSQL
        tracker_changeset_value AS tcv
        INNER JOIN tracker_changeset_value_list AS tcvl ON (
            tcvl.changeset_value_id = tcv.id
        )
        INNER JOIN user ON (
            user.user_id = tcvl.bindvalue_id AND $values_statement
        )
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            "$filter_alias.artifact_id IS NOT NULL",
            $values_statement->values(),
            [],
        );
    }

    private function getWhereForNotIn(
        string $filter_alias,
        InValueWrapper $wrapper,
    ): ParametrizedFromWhere {
        $values_statement = EasyStatement::open()->in(
            "user.user_name IN (?*)",
            array_map(static fn(SimpleValueWrapper $value_wrapper) => $value_wrapper->getValue(), $wrapper->getValueWrappers())
        );

        $from = <<<EOSQL
        tracker_changeset_value AS tcv
        INNER JOIN tracker_changeset_value_list AS tcvl ON (
            tcvl.changeset_value_id = tcv.id
        )
        INNER JOIN user ON (
            user.user_id = tcvl.bindvalue_id AND $values_statement
        )
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            "$filter_alias.artifact_id IS NULL",
            $values_statement->values(),
            [],
        );
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Not implemented yet');
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for User List fields');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for User List fields');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for User List fields');
    }
}
