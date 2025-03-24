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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
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
 * @template-implements ValueWrapperVisitor<NoValueWrapperParameters, Ok<list<string>>|Err<Fault>>
 */
final readonly class DateValuesCollection implements ValueWrapperVisitor
{
    /**
     * @param list<string> $date_values
     */
    private function __construct(
        public array $date_values,
        private bool $is_current_datetime_allowed,
    ) {
    }

    /**
     * @return Ok<self>|Err<Fault>
     */
    public static function fromValueWrapper(ValueWrapper $wrapper, bool $is_current_datetime_allowed): Ok|Err
    {
        return $wrapper->accept(new self([], $is_current_datetime_allowed), new NoValueWrapperParameters())
            ->map(static fn(array $date_values) => new self($date_values, $is_current_datetime_allowed));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        /** @var Ok<list<string>> $ok */
        $ok = Result::ok([(string) $value_wrapper->getValue()]);
        return $ok;
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        if (! $this->is_current_datetime_allowed) {
            return Result::err(InvalidComparisonToCurrentDateTimeFault::build());
        }
        /** @var Ok<list<string>> $ok */
        $ok = Result::ok([$value_wrapper->getValue()->format(DateFormat::DATETIME)]);
        return $ok;
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        return $value_wrapper->getMinValue()
            ->accept($this, $parameters)
            ->andThen(fn(array $min_date_strings) => $value_wrapper->getMaxValue()
                ->accept($this, $parameters)
                ->map(static fn(array $max_date_strings) => array_merge($min_date_strings, $max_date_strings)));
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new \LogicException('Should not end there');
    }

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Err<Fault>
     */
    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        return Result::err(InvalidComparisonToCurrentUserFault::build());
    }

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Err<Fault>
     */
    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        return Result::err(InvalidComparisonToStatusOpenFault::build());
    }
}
