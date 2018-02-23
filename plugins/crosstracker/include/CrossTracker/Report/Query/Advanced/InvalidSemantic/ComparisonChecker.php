<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;

class ComparisonChecker implements ValueWrapperVisitor
{
    /**
     * @param Metadata $metadata
     * @param Comparison $comparison
     * @throws InvalidSemanticComparisonException
     */
    public function checkComparisonIsValid(Metadata $metadata, Comparison $comparison)
    {
        try {
            $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));
        } catch (DateToStringException $exception) {
            throw new DateToStringComparisonException($metadata, $exception->getSubmittedValue());
        }
    }

    public function visitCurrentDateTimeValueWrapper(
        CurrentDateTimeValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new ToNowComparisonException($parameters->getMetadata());
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        if ($parameters->getMetadata()->getName() === AllowedMetadata::STATUS) {
            throw new StatusToSimpleValueComparisonException($value_wrapper->getValue());
        }

        if ($parameters->getMetadata()->getName() === AllowedMetadata::SUBMITTED_ON) {
            $date_validator = new DateFormatValidator(new EmptyStringAllowed(), DateFormat::DATETIME);
            $date_validator->checkValueIsValid($value_wrapper->getValue());
        }
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        // Do nothing, EqualComparison should not receive a BetweenValueWrapper
    }

    public function visitInValueWrapper(
        InValueWrapper $collection_of_value_wrappers,
        ValueWrapperParameters $parameters
    ) {
        // Do nothing, EqualComparison should not receive a InValueWrapper
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        throw new ToMyselfComparisonException($parameters->getMetadata());
    }

    public function visitStatusOpenValueWrapper(
        StatusOpenValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters
    ) {
        if ($parameters->getMetadata()->getName() !== AllowedMetadata::STATUS) {
            throw new ToStatusOpenComparisonException($parameters->getMetadata());
        }
    }
}
