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
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\FieldValueWrapperParameters;
use Tuleap\CrossTracker\Report\Query\ParametrizedWhere;
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
use Tuleap\Tracker\Report\Query\ParametrizedAndFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, ParametrizedWhere>
 */
final readonly class StaticListFromWhereBuilder implements ValueWrapperVisitor
{
    public function getFromWhere(
        DuckTypedField $duck_typed_field,
        Comparison $comparison,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $suffix = spl_object_hash($comparison);

        $tracker_field_alias           = "TF_$suffix";
        $changeset_value_alias         = "CV_$suffix";
        $changeset_value_list_alias    = "CVL_$suffix";
        $tracker_field_list_bind_alias = $this->getAliasForStaticList($comparison);

        $fields_id_statement = EasyStatement::open()->in(
            "$tracker_field_alias.id IN (?*)",
            $duck_typed_field->field_ids
        );
        $from                = <<<EOSQL
        LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND last_changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_list AS $changeset_value_list_alias
            ON $changeset_value_list_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_field_list_bind_static_value AS $tracker_field_list_bind_alias
            ON $tracker_field_list_bind_alias.id = $changeset_value_list_alias.bindvalue_id
        EOSQL;
        $where               = "$tracker_field_alias.id IS NOT NULL";

        return new ParametrizedAndFromWhere(
            new ParametrizedFromWhere($from, $where, $fields_id_statement->values(), []),
            $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($comparison))
        );
    }

    private function getAliasForStaticList(Comparison $comparison): string
    {
        $suffix = spl_object_hash($comparison);
        return "TFLB_$suffix";
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $comparison                    = $parameters->comparison;
        $tracker_field_list_bind_alias = $this->getAliasForStaticList($comparison);

        return match ($comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual($tracker_field_list_bind_alias, $value_wrapper),
            ComparisonType::NotEqual => throw new LogicException('Not implemented yet'),
            ComparisonType::In,
            ComparisonType::NotIn    => throw new LogicException('In comparison expected a InValueWrapper, not a SimpleValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for Static List field')
        };
    }

    private function getWhereForEqual(
        string $tracker_field_list_bind_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere("$tracker_field_list_bind_alias.label IS NULL", []);
        }

        return new ParametrizedWhere(
            "$tracker_field_list_bind_alias.label = ?",
            [$value]
        );
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Not implemented yet');
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
