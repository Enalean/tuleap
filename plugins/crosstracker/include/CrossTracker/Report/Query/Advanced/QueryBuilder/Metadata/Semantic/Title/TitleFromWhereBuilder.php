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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title;

use LogicException;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
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

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class TitleFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(private EasyDB $db)
    {
    }

    public function getFromWhere(Comparison $comparison): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($comparison));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual($value_wrapper),
            ComparisonType::NotEqual => $this->getWhereForNotEqual($value_wrapper),
            default                  => throw new LogicException('Other comparison types are invalid for Title semantic'),
        };
    }

    private function getWhereForEqual(SimpleValueWrapper $wrapper): ParametrizedWhere
    {
        $value = $wrapper->getValue();

        if ($value === '') {
            $match_value      = "= ''";
            $where_parameters = [];
        } else {
            $match_value      = "LIKE ?";
            $where_parameters = [$this->quoteLikeValueSurround($value)];
        }

        $where = <<<EOSQL
        changeset_value_title.changeset_id IS NOT NULL
        AND tracker_changeset_value_title.value $match_value
        EOSQL;

        return new ParametrizedWhere($where, $where_parameters);
    }

    private function getWhereForNotEqual(SimpleValueWrapper $wrapper): ParametrizedWhere
    {
        $value = $wrapper->getValue();

        if ($value === '') {
            return new ParametrizedWhere(
                "tracker_changeset_value_title.value IS NOT NULL AND tracker_changeset_value_title.value <> ''",
                [],
            );
        } else {
            return new ParametrizedWhere(
                "(tracker_changeset_value_title.value IS NULL OR tracker_changeset_value_title.value NOT LIKE ?)",
                [$this->quoteLikeValueSurround($value)],
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
