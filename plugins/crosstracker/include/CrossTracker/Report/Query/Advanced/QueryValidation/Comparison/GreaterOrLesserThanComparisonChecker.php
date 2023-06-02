<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToEmptyStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringException;

abstract class GreaterOrLesserThanComparisonChecker extends ComparisonChecker
{
    /**
     * @throws InvalidQueryException
     */
    public function checkComparisonIsValid(Metadata $metadata, Comparison $comparison)
    {
        if (! in_array($metadata->getName(), AllowedMetadata::DATES)) {
            throw new OperatorNotAllowedForMetadataException($metadata, $this->getOperator());
        }

        try {
            $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));
        } catch (DateToEmptyStringException $e) {
            throw new EmptyStringComparisonException($metadata, $this->getOperator());
        } catch (DateToStringException $e) {
            throw new ToStringComparisonException($metadata, $comparison->getValueWrapper()->getValue());
        }
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $this->date_validator->checkValueIsValid($value_wrapper->getValue());
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        // AllowedMetadata::SUBMITTED_ON can be used with GreaterOrLesserThan operators
    }
}
