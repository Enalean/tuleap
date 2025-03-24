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

use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\OperatorNotAllowedForMetadataException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\StatusToSimpleValueComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToMyselfComparisonException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Comparison\ToNowComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, void>
 */
final readonly class StatusChecker implements ValueWrapperVisitor
{
    /**
     * @throws OperatorNotAllowedForMetadataException
     * @throws StatusToSimpleValueComparisonException
     * @throws ToMyselfComparisonException
     * @throws ToNowComparisonException
     */
    public function checkSemanticIsValidForComparison(Comparison $comparison, Metadata $metadata): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkValueIsValid($comparison, $metadata),
            default => throw new OperatorNotAllowedForMetadataException($metadata, $comparison->getType()->value),
        };
    }

    /**
     * @throws StatusToSimpleValueComparisonException
     * @throws ToMyselfComparisonException
     * @throws ToNowComparisonException
     */
    private function checkValueIsValid(Comparison $comparison, Metadata $metadata): void
    {
        $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        throw new StatusToSimpleValueComparisonException((string) $value_wrapper->getValue());
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        // Do nothing, OPEN() is valid
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new ToNowComparisonException($parameters->getMetadata());
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new ToMyselfComparisonException($parameters->getMetadata());
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new \LogicException('Should not end there');
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new \LogicException('Should not end there');
    }
}
