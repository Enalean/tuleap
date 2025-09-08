<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\NeverThrow;

/**
 * I hold an Ok variant of Result. I will skip mapErr() and orElse() calls.
 * I will call the first parameter of match(). I will return my value for unwrapOr().
 * @template TValue
 * @implements IResult<TValue, never>
 */
final readonly class Ok implements IResult
{
    /**
     * @param TValue $value
     * @psalm-internal Tuleap\NeverThrow
     * @psalm-pure
     */
    public function __construct(
        public mixed $value,
    ) {
    }

    /**
     * @template TNewValue
     * @psalm-param callable(TValue): TNewValue $fn
     * @return Ok<TNewValue>
     */
    #[\Override]
    public function map(callable $fn): Ok
    {
        return new Ok($fn($this->value));
    }

    /**
     * @return Ok<TValue>
     * @psalm-mutation-free
     */
    #[\Override]
    public function mapErr(callable $fn): Ok
    {
        return new Ok($this->value);
    }

    /**
     * @template TNewValue
     * @template TNewError
     * @param callable(TValue): (Ok<TNewValue> | Err<TNewError>) $fn
     * @return Ok<TNewValue> | Err<TNewError>
     */
    #[\Override]
    public function andThen(callable $fn): Ok|Err
    {
        return $fn($this->value);
    }

    /**
     * @return Ok<TValue>
     * @psalm-mutation-free
     */
    #[\Override]
    public function orElse(callable $fn): Ok
    {
        return new Ok($this->value);
    }

    /**
     * @template TReturn
     * @param callable(TValue): TReturn $ok_fn
     * @return TReturn
     */
    #[\Override]
    public function match(callable $ok_fn, callable $err_fn): mixed
    {
        return $ok_fn($this->value);
    }

    /**
     * @return TValue
     * @psalm-mutation-free
     */
    #[\Override]
    public function unwrapOr(mixed $default_value): mixed
    {
        return $this->value;
    }
}
