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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Title;

use LogicException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
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
use Tuleap\Tracker\Tracker;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class TitleFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(private EasyDB $db)
    {
    }

    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $parameters->comparison->getValueWrapper()->accept($this, $parameters);
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual($value_wrapper, $parameters->trackers, $parameters->user),
            ComparisonType::NotEqual => $this->getWhereForNotEqual($value_wrapper, $parameters->trackers, $parameters->user),
            default                  => throw new LogicException('Other comparison types are invalid for Title semantic'),
        };
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getWhereForEqual(
        SimpleValueWrapper $wrapper,
        array $trackers,
        PFUser $user,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        $field_ids = [];
        foreach ($trackers as $tracker) {
            $title_field = $tracker->getTitleField();
            if ($title_field && $title_field->userCanRead($user)) {
                $field_ids[] = $title_field->getId();
            }
        }
        $field_ids_statement = EasyStatement::open()->in('tracker_semantic_title.field_id IN (?*)', $field_ids);

        if ($value === '') {
            $match_value      = "= ''";
            $where_parameters = [];
        } else {
            $match_value      = 'LIKE ?';
            $where_parameters = [$this->quoteLikeValueSurround($value)];
        }

        $where = <<<EOSQL
        changeset_value_title.changeset_id IS NOT NULL
        AND tracker_changeset_value_title.value $match_value
        AND $field_ids_statement
        EOSQL;

        return new ParametrizedWhere(
            $where,
            [...$where_parameters, ...$field_ids],
        );
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getWhereForNotEqual(
        SimpleValueWrapper $wrapper,
        array $trackers,
        PFUser $user,
    ): ParametrizedWhere {
        $value = $wrapper->getValue();

        $field_ids = [];
        foreach ($trackers as $tracker) {
            $title_field = $tracker->getTitleField();
            if ($title_field && $title_field->userCanRead($user)) {
                $field_ids[] = $title_field->getId();
            }
        }
        $field_ids_statement = EasyStatement::open()->in('tracker_semantic_title.field_id IN (?*)', $field_ids);

        if ($value === '') {
            return new ParametrizedWhere(
                "tracker_changeset_value_title.value IS NOT NULL AND tracker_changeset_value_title.value <> '' AND $field_ids_statement",
                $field_ids,
            );
        } else {
            return new ParametrizedWhere(
                "(tracker_changeset_value_title.value IS NULL OR tracker_changeset_value_title.value NOT LIKE ?) AND $field_ids_statement",
                [$this->quoteLikeValueSurround($value), ...$field_ids],
            );
        }
    }

    private function quoteLikeValueSurround(float|int|string $value): string
    {
        return '%' . $this->db->escapeLikeValue((string) $value) . '%';
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Title semantic');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Title semantic');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Title semantic');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Title semantic');
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Title semantic');
    }
}
