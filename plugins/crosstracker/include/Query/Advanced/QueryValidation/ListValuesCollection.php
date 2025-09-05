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
 * @psalm-immutable
 */
final readonly class ListValuesCollection implements ValueWrapperVisitor
{
    /**
     * @param list<string> $list_values
     */
    private function __construct(public array $list_values)
    {
    }

    /**
     * @return Ok<self>|Err<Fault>
     */
    public static function fromValueWrapper(ValueWrapper $wrapper): Ok|Err
    {
        return $wrapper->accept(new self([]), new NoValueWrapperParameters())
            ->map(static fn(array $list_values) => new self($list_values));
    }

    #[\Override]
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        /** @var Ok<list<string>> $ok */
        $ok = Result::ok([(string) $value_wrapper->getValue()]);
        return $ok;
    }

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Ok<list<string>>|Err<Fault>
     */
    #[\Override]
    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        $usernames = [];
        foreach ($collection_of_value_wrappers->getValueWrappers() as $wrapper) {
            $result = $wrapper->accept($this, $parameters);
            if (Result::isErr($result)) {
                return $result;
            }
            if (count($result->value) > 0) {
                $usernames[] = (string) $result->value[0];
            }
        }
        return Result::ok($usernames);
    }

    #[\Override]
    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        $value = $value_wrapper->getValue();
        if ($value === '') {
            return Result::err(MyselfNotAllowedForAnonymousFault::build());
        }
        /** @var Ok<list<string>> $ok */
        $ok = Result::ok([$value]);
        return $ok;
    }

    #[\Override]
    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new \LogicException('Should not end there');
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

    /**
     * @param NoValueWrapperParameters $parameters
     * @return Err<Fault>
     */
    #[\Override]
    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        return Result::err(InvalidComparisonToStatusOpenFault::build());
    }
}
