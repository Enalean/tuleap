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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\EmptyStringComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ListToMyselfForAnonymousComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToNowComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStringComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidComparisonToCurrentDateTimeFault;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidComparisonToStatusOpenFault;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\ListValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\MyselfNotAllowedForAnonymousFault;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\User\RetrieveUserByUserName;

final readonly class AssignedToChecker
{
    public function __construct(private RetrieveUserByUserName $user_retriever)
    {
    }

    /**
     * @throws EmptyStringComparisonException
     * @throws ListToMyselfForAnonymousComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws ToNowComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToStringComparisonException
     */
    public function checkSemanticIsValidForComparison(Comparison $comparison, Metadata $metadata): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkListValueIsValid($comparison, $metadata, false),
            ComparisonType::In,
            ComparisonType::NotIn => $this->checkListValueIsValid($comparison, $metadata, true),
            default => throw new OperatorNotAllowedForMetadataException($metadata, $comparison->getType()->value),
        };
    }

    /**
     * @throws EmptyStringComparisonException
     * @throws ListToMyselfForAnonymousComparisonException
     * @throws ToNowComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToStringComparisonException
     */
    private function checkListValueIsValid(
        Comparison $comparison,
        Metadata $metadata,
        bool $is_empty_string_a_problem,
    ): void {
        ListValuesCollection::fromValueWrapper($comparison->getValueWrapper())
            ->match(function (ListValuesCollection $collection) use ($comparison, $metadata, $is_empty_string_a_problem) {
                foreach ($collection->list_values as $username) {
                    if ($username === '') {
                        if (! $is_empty_string_a_problem) {
                            continue;
                        }
                        throw new EmptyStringComparisonException($metadata, $comparison->getType()->value);
                    }
                    $user = $this->user_retriever->getUserByUserName($username);
                    if ($user === null) {
                        throw new ToStringComparisonException($metadata, $username);
                    }
                }
            }, static function (Fault $fault) use ($metadata) {
                match ($fault::class) {
                    InvalidComparisonToStatusOpenFault::class => throw new ToStatusOpenComparisonException($metadata),
                    InvalidComparisonToCurrentDateTimeFault::class => throw new ToNowComparisonException($metadata),
                    MyselfNotAllowedForAnonymousFault::class => throw new ListToMyselfForAnonymousComparisonException($metadata),
                };
            });
    }
}
