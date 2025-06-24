<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;

use LogicException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
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
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class DescriptionFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(
        private EasyDB $db,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
    ) {
    }

    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $field_ids = [];
        foreach ($parameters->trackers as $tracker) {
            $description_field = $this->retrieve_description_field->fromTracker($tracker);
            if ($description_field && $description_field->userCanRead($parameters->user)) {
                $field_ids[] = $description_field->getId();
            }
        }
        $field_ids_statement = EasyStatement::open()->in('tracker_semantic_description.field_id IN (?*)', $field_ids);

        $from = <<<EOSQL
        INNER JOIN tracker_semantic_description
            ON (tracker_semantic_description.tracker_id = artifact.tracker_id AND $field_ids_statement)
        LEFT JOIN tracker_changeset_value AS changeset_value_description ON (
            changeset_value_description.changeset_id = artifact.last_changeset_id
            AND changeset_value_description.field_id = tracker_semantic_description.field_id
        )
        LEFT JOIN tracker_changeset_value_text AS tracker_changeset_value_description
            ON (tracker_changeset_value_description.changeset_value_id = changeset_value_description.id)
        EOSQL;

        $where = $parameters->comparison->getValueWrapper()->accept($this, $parameters);
        return new ParametrizedFromWhere(
            $from,
            $where->getWhere(),
            $field_ids_statement->values(),
            $where->getWhereParameters(),
        );
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual($value_wrapper),
            ComparisonType::NotEqual => $this->getWhereForNotEqual($value_wrapper),
            default                  => throw new LogicException('Other comparison types are invalid for Description semantic'),
        };
    }

    private function getWhereForEqual(SimpleValueWrapper $wrapper): ParametrizedWhere
    {
        $value = $wrapper->getValue();

        if ($value === '') {
            $match_value      = "= ''";
            $where_parameters = [];
        } else {
            $match_value      = 'LIKE ?';
            $where_parameters = [$this->quoteLikeValueSurround($value)];
        }

        return new ParametrizedWhere(
            "tracker_changeset_value_description.value $match_value",
            $where_parameters,
        );
    }

    private function getWhereForNotEqual(SimpleValueWrapper $wrapper): ParametrizedWhere
    {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere(
                "tracker_changeset_value_description.value IS NOT NULL AND tracker_changeset_value_description.value <> ''",
                [],
            );
        }

        return new ParametrizedWhere(
            '(tracker_changeset_value_description.value IS NULL OR tracker_changeset_value_description.value NOT LIKE ?)',
            [$this->quoteLikeValueSurround($value)],
        );
    }

    private function quoteLikeValueSurround(float|int|string $value): string
    {
        return '%' . $this->db->escapeLikeValue((string) $value) . '%';
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Description semantic');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Description semantic');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Description semantic');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Description semantic');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Description semantic');
    }
}
