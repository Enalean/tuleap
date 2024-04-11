<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\EmptyStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListToMyselfForAnonymousComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToAnyStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToEmptyStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToIntegerLesserThanOneException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToMyselfComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToNowComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ToStringComparisonException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\ArtifactIdsValuesCollection;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidComparisonToEmptyStringFault;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidComparisonToAnyStringFault;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidComparisonToIntegerLesserThanOneFault;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidComparisonToCurrentDateTimeFault;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidComparisonToCurrentUserFault;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidComparisonToStatusOpenFault;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class ArtifactIdMetadataChecker
{
    /**
     * @throws ToStatusOpenComparisonException
     * @throws ListToMyselfForAnonymousComparisonException
     * @throws ToStringComparisonException
     * @throws ToNowComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws EmptyStringComparisonException
     */
    public function checkAlwaysThereFieldIsValidForComparison(Comparison $comparison, Metadata $metadata): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual,
            ComparisonType::LesserThan => $this->checkIntegerValueIsValid($comparison, $metadata),
            default => throw new OperatorNotAllowedForMetadataException($metadata, $comparison->getType()->value),
        };
    }

    /**
     * @throws EmptyStringComparisonException
     * @throws ToStringComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToNowComparisonException
     * @throws ListToMyselfForAnonymousComparisonException
     */
    private function checkIntegerValueIsValid(
        Comparison $comparison,
        Metadata $metadata,
    ): void {
        ArtifactIdsValuesCollection::fromValueWrapper($comparison->getValueWrapper())
            ->mapErr(
                static function (Fault $fault) use ($metadata) {
                    match ($fault::class) {
                        InvalidComparisonToEmptyStringFault::class => throw new ToEmptyStringComparisonException($metadata),
                        InvalidComparisonToAnyStringFault::class => throw new ToAnyStringComparisonException($metadata),
                        InvalidComparisonToIntegerLesserThanOneFault::class => throw new ToIntegerLesserThanOneException($metadata),
                        InvalidComparisonToStatusOpenFault::class => throw new ToStatusOpenComparisonException($metadata),
                        InvalidComparisonToCurrentDateTimeFault::class => throw new ToNowComparisonException($metadata),
                        InvalidComparisonToCurrentUserFault::class => throw new ToMyselfComparisonException($metadata),
                    };
                }
            );
    }
}
