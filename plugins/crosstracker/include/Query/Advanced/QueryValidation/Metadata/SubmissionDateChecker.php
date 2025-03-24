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
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorToNowComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToMyselfComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStatusOpenComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToStringComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DateValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidComparisonToCurrentDateTimeFault;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidComparisonToCurrentUserFault;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidComparisonToStatusOpenFault;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToEmptyStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;

final readonly class SubmissionDateChecker
{
    /**
     * @throws EmptyStringComparisonException
     * @throws OperatorNotAllowedForMetadataException
     * @throws OperatorToNowComparisonException
     * @throws ToMyselfComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToStringComparisonException
     */
    public function checkAlwaysThereFieldIsValidForComparison(Comparison $comparison, Metadata $metadata): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkValueIsValid($comparison, $metadata, false),
            ComparisonType::Between,
            ComparisonType::GreaterThan,
            ComparisonType::GreaterThanOrEqual,
            ComparisonType::LesserThan,
            ComparisonType::LesserThanOrEqual => $this->checkValueIsValid($comparison, $metadata, true),
            default => throw new OperatorNotAllowedForMetadataException($metadata, $comparison->getType()->value),
        };
    }

    /**
     * @throws EmptyStringComparisonException
     * @throws OperatorToNowComparisonException
     * @throws ToMyselfComparisonException
     * @throws ToStatusOpenComparisonException
     * @throws ToStringComparisonException
     */
    private function checkValueIsValid(
        Comparison $comparison,
        Metadata $metadata,
        bool $is_current_datetime_allowed,
    ): void {
        $validator = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);

        DateValuesCollection::fromValueWrapper($comparison->getValueWrapper(), $is_current_datetime_allowed)
            ->match(function (DateValuesCollection $collection) use ($comparison, $metadata, $validator) {
                foreach ($collection->date_values as $date_string) {
                    try {
                        $validator->checkValueIsValid($date_string);
                    } catch (DateToEmptyStringException) {
                        throw new EmptyStringComparisonException($metadata, $comparison->getType()->value);
                    } catch (DateToStringException) {
                        throw new ToStringComparisonException($metadata, $comparison->getType()->value);
                    }
                }
            }, static function (Fault $fault) use ($comparison, $metadata) {
                match ($fault::class) {
                    InvalidComparisonToCurrentDateTimeFault::class => throw new OperatorToNowComparisonException(
                        $metadata,
                        $comparison->getType()->value
                    ),
                    InvalidComparisonToStatusOpenFault::class => throw new ToStatusOpenComparisonException($metadata),
                    InvalidComparisonToCurrentUserFault::class => throw new ToMyselfComparisonException($metadata),
                };
            });
    }
}
