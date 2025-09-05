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


namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NoValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;

/**
 * @template-implements ValueWrapperVisitor<NoValueWrapperParameters, Ok<list<int>>|Err<Fault>>
 */
final readonly class ArtifactIdsValuesCollection implements ValueWrapperVisitor
{
    /**
     * @param list<int> $artifact_ids
     */
    private function __construct(public array $artifact_ids)
    {
    }

    /**
     * @return Ok<self>|Err<Fault>
     */
    public static function fromValueWrapper(ValueWrapper $wrapper): Ok|Err
    {
        return $wrapper->accept(new self([]), new NoValueWrapperParameters())
            ->map(static fn(array $artifact_ids) => new self($artifact_ids));
    }

    #[\Override]
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        $value = $value_wrapper->getValue();
        if ($value === '') {
            return Result::err(InvalidComparisonToEmptyStringFault::build());
        }

        if (! is_int($value)) {
            return Result::err(InvalidComparisonToAnyStringFault::build());
        }

        if ($value <= 0) {
            return Result::err(InvalidComparisonToIntegerLesserThanOneFault::build());
        }

        /** @var Ok<list<int>> $ok */
        $ok = Result::ok([$value]);
        return $ok;
    }

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Err<Fault>
     */
    #[\Override]
    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        return Result::err(InvalidComparisonToStatusOpenFault::build());
    }

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Err<Fault>
     */
    #[\Override]
    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        return Result::err(InvalidComparisonToCurrentDateTimeFault::build());
    }

    #[\Override]
    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        return $value_wrapper->getMinValue()
            ->accept($this, $parameters)
            ->andThen(fn(array $min_artifact_id) => $value_wrapper->getMaxValue()
                ->accept($this, $parameters)
                ->map(static fn(array $max_artifact_id) => array_merge($min_artifact_id, $max_artifact_id)))
            ->andThen(
                function (array $values) {
                    if ($values[0] > $values[1]) {
                        return Result::err(InvalidComparisonWithBetweenValuesMinGreaterThanMaxFault::build());
                    }

                    /** @var Ok<list<int>> $ok */
                    $ok = Result::ok($values);
                    return $ok;
                }
            );
    }

    #[\Override]
    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new \LogicException('Should not end there');
    }

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Err<Fault>
     */
    #[\Override]
    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        return Result::err(InvalidComparisonToCurrentUserFault::build());
    }
}
