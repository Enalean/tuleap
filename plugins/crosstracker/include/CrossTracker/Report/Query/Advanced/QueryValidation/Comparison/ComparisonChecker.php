<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToEmptyStringException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateToStringException;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, void>
 */
abstract class ComparisonChecker implements ValueWrapperVisitor
{
    public function __construct(
        protected readonly DateFormatValidator $date_validator,
        protected readonly ListValueValidator $list_value_validator,
    ) {
    }

    abstract public function getOperator(): string;

    /**
     * @throws InvalidQueryException
     */
    public function checkComparisonIsValid(Metadata $metadata, Comparison $comparison)
    {
        try {
            $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));
        } catch (DateToStringException $exception) {
            throw new ToStringComparisonException($metadata, $exception->getSubmittedValue());
        } catch (DateToEmptyStringException $exception) {
            throw new EmptyStringComparisonException($metadata, $this->getOperator());
        } catch (NonExistentListValueException $exception) {
            throw new ToStringComparisonException($metadata, $exception->getSubmittedValue());
        } catch (ListToEmptyStringException $exception) {
            throw new EmptyStringComparisonException($metadata, $this->getOperator());
        }
    }

    public function visitCurrentDateTimeValueWrapper(
        CurrentDateTimeValueWrapper $value_wrapper,
        $parameters,
    ) {
        throw new ToNowComparisonException($parameters->getMetadata());
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $metadata = $parameters->getMetadata();
        if ($metadata->getName() === AllowedMetadata::STATUS) {
            throw new StatusToSimpleValueComparisonException($value_wrapper->getValue());
        }

        if (in_array($metadata->getName(), AllowedMetadata::DATES)) {
            $this->date_validator->checkValueIsValid($value_wrapper->getValue());
        }

        if (in_array($metadata->getName(), AllowedMetadata::USERS)) {
            $this->list_value_validator->checkValueIsValid($value_wrapper->getValue());
        }
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        // Do nothing, EqualComparison should not receive a BetweenValueWrapper
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        // Do nothing, EqualComparison should not receive a InValueWrapper
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        $metadata = $parameters->getMetadata();
        if (! in_array($metadata->getName(), AllowedMetadata::USERS)) {
            throw new ToMyselfComparisonException($metadata);
        }
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        if ($parameters->getMetadata()->getName() !== AllowedMetadata::STATUS) {
            throw new ToStatusOpenComparisonException($parameters->getMetadata());
        }
    }
}
