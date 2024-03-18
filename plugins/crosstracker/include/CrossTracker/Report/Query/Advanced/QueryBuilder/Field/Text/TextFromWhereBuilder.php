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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Text;

use LogicException;
use ParagonIE\EasyDB\EasyDB;
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
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements ValueWrapperVisitor<FieldValueWrapperParameters, ParametrizedWhere>
 */
final readonly class TextFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(private EasyDB $db)
    {
    }

    public function getFromWhere(
        DuckTypedField $duck_typed_field,
        Comparison $comparison,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $suffix = spl_object_hash($comparison);

        $tracker_field_alias        = "TF_$suffix";
        $changeset_value_alias      = "CV_$suffix";
        $changeset_value_text_alias = $this->getAliasForText($comparison);

        $fields_id_statement = EasyStatement::open()->in(
            "$tracker_field_alias.id IN (?*)",
            $duck_typed_field->field_ids
        );
        $from                = <<<EOSQL
        INNER JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND last_changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_text AS $changeset_value_text_alias
            ON $changeset_value_text_alias.changeset_value_id = $changeset_value_alias.id
        EOSQL;

        $where = $comparison->getValueWrapper()->accept($this, new FieldValueWrapperParameters($comparison));
        return new ParametrizedFromWhere(
            $from,
            $where->getWhere(),
            $fields_id_statement->values(),
            $where->getWhereParameters(),
        );
    }

    private function getAliasForText(Comparison $comparison): string
    {
        $suffix = spl_object_hash($comparison);
        return "CVText_$suffix";
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $comparison                 = $parameters->comparison;
        $changeset_value_text_alias = $this->getAliasForText($comparison);

        return match ($comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual($changeset_value_text_alias, $value_wrapper),
            ComparisonType::NotEqual => $this->getWhereForNotEqual($changeset_value_text_alias, $value_wrapper),
            default                  => throw new LogicException('Other comparison types are invalid for Text field')
        };
    }

    private function getWhereForEqual(
        string $changeset_value_text_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere("$changeset_value_text_alias.value = ''", []);
        }

        return new ParametrizedWhere(
            "$changeset_value_text_alias.value LIKE ?",
            [$this->quoteLikeValueSurround($value)]
        );
    }

    private function getWhereForNotEqual(
        string $changeset_value_text_alias,
        SimpleValueWrapper $wrapper,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere(
                "$changeset_value_text_alias.value IS NOT NULL AND $changeset_value_text_alias.value != ''",
                []
            );
        }

        return new ParametrizedWhere(
            "($changeset_value_text_alias.value IS NULL OR $changeset_value_text_alias.value NOT LIKE ?)",
            [$this->quoteLikeValueSurround($value)]
        );
    }

    private function quoteLikeValueSurround(float|int|string $value): string
    {
        return '%' . $this->db->escapeLikeValue((string) $value) . '%';
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Text fields');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Text fields');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Text fields');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Text fields');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Text fields');
    }
}
