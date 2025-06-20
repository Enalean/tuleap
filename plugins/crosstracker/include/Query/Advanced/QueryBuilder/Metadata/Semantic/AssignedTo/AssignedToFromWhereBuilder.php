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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
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
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Tracker;
use Tuleap\User\RetrieveUserByUserName;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedFromWhere>
 */
final readonly class AssignedToFromWhereBuilder implements ValueWrapperVisitor
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
        return match ($parameters->comparison->getType()) {
            ComparisonType::Equal    => $this->getFromWhereForEqual($value_wrapper, $parameters->trackers, $parameters->user),
            ComparisonType::NotEqual => $this->getFromWhereForNotEqual($value_wrapper, $parameters->trackers, $parameters->user),
            ComparisonType::In,
            ComparisonType::NotIn    => throw new LogicException('In comparison expected a InValueWrapper, not a SimpleValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for AssignedTo semantic'),
        };
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getFromWhereForEqual(
        SimpleValueWrapper $value_wrapper,
        array $trackers,
        PFUser $user,
    ): ParametrizedFromWhere {
        $value = $value_wrapper->getValue();

        $field_ids = [];
        foreach ($trackers as $tracker) {
            $assigned_to_field = $tracker->getContributorField();
            if ($assigned_to_field && $assigned_to_field->userCanRead($user)) {
                $field_ids[] = $assigned_to_field->getId();
            }
        }

        if ($value === '') {
            $field_ids_statement = EasyStatement::open()->in('empty_assigned_to_field.field_id IN (?*)', $field_ids);
            $from                = <<<EOSQL
            INNER JOIN tracker_semantic_contributor AS empty_assigned_to_field
                ON (empty_assigned_to_field.tracker_id = artifact.tracker_id AND $field_ids_statement)
            LEFT JOIN (
                tracker_changeset_value AS changeset_value_assigned_to
                INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
                    ON (tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id)
            ) ON (
                changeset_value_assigned_to.changeset_id = artifact.last_changeset_id
                AND changeset_value_assigned_to.field_id = empty_assigned_to_field.field_id
            )
            EOSQL;

            return new ParametrizedFromWhere(
                $from,
                '(changeset_value_assigned_to.changeset_id IS NULL OR tracker_changeset_value_assigned_to.bindvalue_id = ?)',
                $field_ids_statement->values(),
                [Tracker_FormElement_Field_List::NONE_VALUE]
            );
        }

        $field_ids_statement = EasyStatement::open()->in('equal_assigned_to_field.field_id IN (?*)', $field_ids);
        $from                = <<<EOSQL
        INNER JOIN tracker_semantic_contributor AS equal_assigned_to_field
            ON (equal_assigned_to_field.tracker_id = artifact.tracker_id AND $field_ids_statement)
        EOSQL;

        $where = <<<EOSQL
        artifact.last_changeset_id IN (
            SELECT changeset_value_assigned_to.changeset_id
            FROM tracker_changeset_value AS changeset_value_assigned_to
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
                ON (tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id)
            WHERE tracker_changeset_value_assigned_to.bindvalue_id = ?
                AND changeset_value_assigned_to.field_id = equal_assigned_to_field.field_id
        )
        EOSQL;

        $user = $this->user_retriever->getUserByUserName((string) $value);
        assert($user !== null);

        return new ParametrizedFromWhere(
            $from,
            $where,
            $field_ids_statement->values(),
            [$user->getId()]
        );
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getFromWhereForNotEqual(
        SimpleValueWrapper $value_wrapper,
        array $trackers,
        PFUser $user,
    ): ParametrizedFromWhere {
        $value = $value_wrapper->getValue();

        $field_ids = [];
        foreach ($trackers as $tracker) {
            $assigned_to_field = $tracker->getContributorField();
            if ($assigned_to_field && $assigned_to_field->userCanRead($user)) {
                $field_ids[] = $assigned_to_field->getId();
            }
        }

        $field_ids_statement = EasyStatement::open()->in('assigned_to_field_not_equal.field_id IN (?*)', $field_ids);
        $from                = <<<EOSQL
        INNER JOIN tracker_semantic_contributor AS assigned_to_field_not_equal
            ON (assigned_to_field_not_equal.tracker_id = artifact.tracker_id AND $field_ids_statement)
        LEFT JOIN (
            tracker_changeset_value AS changeset_value_assigned_to_not_equal
            INNER JOIN tracker_changeset_value_list AS changeset_value_list_assigned_to_not_equal
                ON (changeset_value_list_assigned_to_not_equal.changeset_value_id = changeset_value_assigned_to_not_equal.id)
        ) ON (
            changeset_value_assigned_to_not_equal.changeset_id = artifact.last_changeset_id
            AND changeset_value_assigned_to_not_equal.field_id = assigned_to_field_not_equal.field_id
        )
        EOSQL;

        if ($value === '') {
            return new ParametrizedFromWhere(
                $from,
                'changeset_value_assigned_to_not_equal.changeset_id IS NOT NULL AND changeset_value_list_assigned_to_not_equal.bindvalue_id != ?',
                $field_ids_statement->values(),
                [Tracker_FormElement_Field_List::NONE_VALUE]
            );
        }

        $tracker_ids_condition = EasyStatement::open()->in(
            'artifact.tracker_id IN (?*)',
            array_map(
                static fn(Tracker $tracker) => $tracker->getId(),
                $trackers
            )
        );

        $where = <<<EOSQL
        changeset_value_assigned_to_not_equal.changeset_id IS NOT NULL
        AND artifact.id NOT IN (
            SELECT artifact.id
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            INNER JOIN tracker_semantic_contributor AS not_equal_assigned_to_field
            ON (
                not_equal_assigned_to_field.tracker_id = artifact.tracker_id
            )
            LEFT JOIN (
                tracker_changeset_value AS changeset_value_assigned_to
                INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
                ON (
                    tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id
                )
            ) ON (
                changeset_value_assigned_to.changeset_id = artifact.last_changeset_id
                AND changeset_value_assigned_to.field_id = not_equal_assigned_to_field.field_id
            )
            WHERE $tracker_ids_condition
                AND changeset_value_assigned_to.changeset_id IS NOT NULL
                AND tracker_changeset_value_assigned_to.bindvalue_id = ?
        )
        EOSQL;

        $user = $this->user_retriever->getUserByUserName((string) $value);
        assert($user !== null);

        return new ParametrizedFromWhere(
            $from,
            $where,
            $field_ids_statement->values(),
            [...$tracker_ids_condition->values(), $user->getId()],
        );
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        return match ($parameters->comparison->getType()) {
            ComparisonType::In       => $this->getFromWhereForIn($collection_of_value_wrappers, $parameters->trackers, $parameters->user),
            ComparisonType::NotIn    => $this->getFromWhereForNotIn($collection_of_value_wrappers, $parameters->trackers, $parameters->user),
            ComparisonType::Equal    => throw new LogicException('Equal comparison expected a SimpleValueWrapper, not a InValueWrapper'),
            ComparisonType::NotEqual => throw new LogicException('Not Equal comparison expected a SimpleValueWrapper, not a InValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for AssignedTo semantic'),
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

    /**
     * @param Tracker[] $trackers
     */
    private function getFromWhereForIn(
        InValueWrapper $wrapper,
        array $trackers,
        PFUser $user,
    ): ParametrizedFromWhere {
        $values           = $this->parseValueWrappersToValues($wrapper->getValueWrappers());
        $values_condition = EasyStatement::open()->in(
            'tracker_changeset_value_assigned_to.bindvalue_id IN (?*)',
            $this->mapUsernameToUserId($values)
        );

        $field_ids = [];
        foreach ($trackers as $tracker) {
            $assigned_to_field = $tracker->getContributorField();
            if ($assigned_to_field && $assigned_to_field->userCanRead($user)) {
                $field_ids[] = $assigned_to_field->getId();
            }
        }
        $field_ids_statement = EasyStatement::open()->in('equal_assigned_to_field.field_id IN (?*)', $field_ids);

        $from = <<<EOSQL
        INNER JOIN tracker_semantic_contributor AS equal_assigned_to_field
        ON (
            equal_assigned_to_field.tracker_id = artifact.tracker_id AND $field_ids_statement
        )
        EOSQL;

        $where = <<<EOSQL
        artifact.last_changeset_id IN (
            SELECT changeset_value_assigned_to.changeset_id
            FROM tracker_changeset_value AS changeset_value_assigned_to
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
            ON (
                tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id
            )
            WHERE $values_condition
              AND changeset_value_assigned_to.field_id = equal_assigned_to_field.field_id
        )
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            $where,
            $field_ids_statement->values(),
            $values_condition->values()
        );
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getFromWhereForNotIn(
        InValueWrapper $wrapper,
        array $trackers,
        PFUser $user,
    ): ParametrizedFromWhere {
        $tracker_ids_condition = EasyStatement::open()->in(
            'artifact.tracker_id IN (?*)',
            array_map(
                static fn(Tracker $tracker) => $tracker->getId(),
                $trackers
            )
        );

        $field_ids = [];
        foreach ($trackers as $tracker) {
            $assigned_to_field = $tracker->getContributorField();
            if ($assigned_to_field && $assigned_to_field->userCanRead($user)) {
                $field_ids[] = $assigned_to_field->getId();
            }
        }
        $field_ids_statement = EasyStatement::open()->in('assigned_to_field_not_equal.field_id IN (?*)', $field_ids);

        $values           = $this->parseValueWrappersToValues($wrapper->getValueWrappers());
        $values_condition = EasyStatement::open()->in(
            'tracker_changeset_value_assigned_to.bindvalue_id IN (?*)',
            $this->mapUsernameToUserId($values)
        );

        $from = <<<EOSQL
        INNER JOIN tracker_semantic_contributor AS assigned_to_field_not_equal
            ON (assigned_to_field_not_equal.tracker_id = artifact.tracker_id AND $field_ids_statement)
        LEFT JOIN (
            tracker_changeset_value AS changeset_value_assigned_to_not_equal
            INNER JOIN tracker_changeset_value_list AS changeset_value_list_assigned_to_not_equal
                ON (changeset_value_list_assigned_to_not_equal.changeset_value_id = changeset_value_assigned_to_not_equal.id)
        ) ON (
            changeset_value_assigned_to_not_equal.changeset_id = artifact.last_changeset_id
            AND changeset_value_assigned_to_not_equal.field_id = assigned_to_field_not_equal.field_id
        )
        EOSQL;

        $where = <<<EOSQL
        changeset_value_assigned_to_not_equal.changeset_id IS NOT NULL
        AND artifact.id NOT IN (
            SELECT artifact.id
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            INNER JOIN tracker_semantic_contributor AS not_equal_assigned_to_field
                ON (not_equal_assigned_to_field.tracker_id = artifact.tracker_id)
            LEFT JOIN (
                tracker_changeset_value AS changeset_value_assigned_to
                INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
                    ON (tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id)
            ) ON (
                changeset_value_assigned_to.changeset_id = artifact.last_changeset_id
                AND changeset_value_assigned_to.field_id = not_equal_assigned_to_field.field_id
            )
            WHERE $tracker_ids_condition
                AND changeset_value_assigned_to.changeset_id IS NOT NULL
                AND $values_condition
        )
        EOSQL;

        return new ParametrizedFromWhere(
            $from,
            $where,
            $field_ids_statement->values(),
            [...$tracker_ids_condition->values(), ...$values_condition->values()]
        );
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        $simple_value_wrapper = new SimpleValueWrapper((string) $value_wrapper->getValue());

        $comparison = $parameters->comparison;
        return match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->visitSimpleValueWrapper($simple_value_wrapper, $parameters),
            ComparisonType::In,
            ComparisonType::NotIn,   => throw new LogicException('In comparison expected a InValueWrapper, not a CurrentUserValueWrapper'),
            default                  => throw new LogicException('Other comparison types are invalid for AssignedTo semantic')
        };
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to status open should have been flagged as invalid for AssignedTo semantic');
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for AssignedTo semantic');
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for AssignedTo semantic');
    }
}
