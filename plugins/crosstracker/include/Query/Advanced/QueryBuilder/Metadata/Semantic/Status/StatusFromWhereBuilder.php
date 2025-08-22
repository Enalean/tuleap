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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
use Tuleap\CrossTracker\Query\ParametrizedWhere;
use Tuleap\Tracker\FormElement\Field\ListField;
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
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final class StatusFromWhereBuilder implements ValueWrapperVisitor
{
    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from = <<<EOSQL
        LEFT JOIN (
            SELECT artifact.id AS artifact_id
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset_value AS tcv
                ON (tcv.changeset_id = artifact.last_changeset_id)
            INNER JOIN tracker_semantic_status AS tss
                ON (tss.field_id = tcv.field_id)
            INNER JOIN tracker_changeset_value_list AS tcvl
                ON (tcvl.changeset_value_id = tcv.id AND (tcvl.bindvalue_id = tss.open_value_id OR tcvl.bindvalue_id = ?))
        ) AS artifact_filter ON (artifact.id = artifact_filter.artifact_id)
        EOSQL;

        $where = $parameters->comparison->getValueWrapper()->accept($this, $parameters);
        return new ParametrizedFromWhere(
            $from,
            $where->getWhere(),
            [ListField::NONE_VALUE],
            $where->getWhereParameters(),
        );
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters): ParametrizedWhere
    {
        $tracker_ids = [];
        foreach ($parameters->trackers as $tracker) {
            $status_field = $tracker->getStatusField();
            if ($status_field && $status_field->userCanRead($parameters->user)) {
                $tracker_ids[] = $tracker->getId();
            }
        }
        $tracker_ids_statement = EasyStatement::open()->in('tracker.id IN (?*)', $tracker_ids);

        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => new ParametrizedWhere("artifact_filter.artifact_id IS NOT NULL AND $tracker_ids_statement", $tracker_ids),
            ComparisonType::NotEqual => new ParametrizedWhere("artifact_filter.artifact_id IS NULL AND $tracker_ids_statement", $tracker_ids),
            default                  => throw new LogicException('Other comparison types are invalid for Status semantic'),
        };
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
