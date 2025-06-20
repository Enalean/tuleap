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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users;

use LogicException;
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Tracker;
use Tuleap\User\RetrieveUserByUserName;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class UsersFromWhereBuilder implements ValueWrapperVisitor
{
    public function __construct(
        private RetrieveUserByUserName $user_retriever,
    ) {
    }

    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $parameters->comparison->getValueWrapper()->accept($this, $parameters);
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $value = $value_wrapper->getValue();
        if ($value === '') {
            throw new LogicException('Comparison to empty string should have been flagged as invalid for Users metadata');
        }

        $tracker_ids_statement = EasyStatement::open()->in(
            'tracker.id IN (?*)',
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers)
        );

        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => $this->getWhereForEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::NotEqual => $this->getWhereForNotEqual((string) $value, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::In,
            ComparisonType::NotIn    => throw new LogicException('In comparison expected a InValueWrapper, not a SimpleValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for Users metadata')
        };
    }

    private function getWhereForEqual(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $user = $this->user_retriever->getUserByUserName($value);
        assert($user !== null);

        return new ParametrizedWhere(
            "$field_alias = ? AND $tracker_ids_statement",
            [$user->getId(), ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForNotEqual(string $value, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $user = $this->user_retriever->getUserByUserName($value);
        assert($user !== null);

        return new ParametrizedWhere(
            "$field_alias != ? AND $tracker_ids_statement",
            [$user->getId(), ...array_values($tracker_ids_statement->values())]
        );
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        $tracker_ids_statement = EasyStatement::open()->in(
            'tracker.id IN (?*)',
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers)
        );

        return match ($parameters->comparison->getType()) {
            ComparisonType::In       => $this->getWhereForIn($collection_of_value_wrappers, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::NotIn    => $this->getWhereForNotIn($collection_of_value_wrappers, $parameters->field_alias, $tracker_ids_statement),
            ComparisonType::Equal    => throw new LogicException('Equal comparison expected a SimpleValueWrapper, not a InValueWrapper'),
            ComparisonType::NotEqual => throw new LogicException('Not Equal comparison expected a SimpleValueWrapper, not a InValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for Users metadata')
        };
    }

    /**
     * @param ValueWrapper[] $value_wrappers
     * @return string[]
     */
    private function parseValueWrappersToValues(array $value_wrappers): array
    {
        return array_map(
            static fn(ValueWrapper $value_wrapper) => match ($value_wrapper::class) {
                SimpleValueWrapper::class      => (string) $value_wrapper->getValue(),
                CurrentUserValueWrapper::class => (string) $value_wrapper->getValue(),
                default                        => throw new LogicException('Expected a SimpleValueWrapper or a CurrentUserValueWrapper, not a ' . $value_wrapper::class),
            },
            $value_wrappers
        );
    }

    /**
     * @param string[] $usernames
     * @return int[]
     */
    private function mapUsernameToUserId(array $usernames): array
    {
        return array_map(
            function (string $name) {
                $user = $this->user_retriever->getUserByUserName($name);
                assert($user !== null);
                return (int) $user->getId();
            },
            $usernames,
        );
    }

    private function getWhereForIn(InValueWrapper $wrapper, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $values       = $this->parseValueWrappersToValues($wrapper->getValueWrappers());
        $in_condition = EasyStatement::open()->in(
            "$field_alias IN (?*)",
            $this->mapUsernameToUserId($values)
        );

        return new ParametrizedWhere(
            $in_condition . " AND $tracker_ids_statement",
            [...array_values($in_condition->values()), ...array_values($tracker_ids_statement->values())]
        );
    }

    private function getWhereForNotIn(InValueWrapper $wrapper, string $field_alias, EasyStatement $tracker_ids_statement): ParametrizedWhere
    {
        $values       = $this->parseValueWrappersToValues($wrapper->getValueWrappers());
        $in_condition = EasyStatement::open()->in(
            "$field_alias NOT IN (?*)",
            $this->mapUsernameToUserId($values)
        );

        return new ParametrizedWhere(
            $in_condition . " AND $tracker_ids_statement",
            [...array_values($in_condition->values()), ...array_values($tracker_ids_statement->values())]
        );
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        $simple_value_wrapper = new SimpleValueWrapper((string) $value_wrapper->getValue());

        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->visitSimpleValueWrapper($simple_value_wrapper, $parameters),
            ComparisonType::In,
            ComparisonType::NotIn,   => throw new LogicException('In comparison expected a InValueWrapper, not a CurrentUserValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for Users metadata')
        };
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for Users semantic');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Users semantic');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Users semantic');
    }
}
