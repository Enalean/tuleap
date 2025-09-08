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
 * I hold an Error variant of Result. I will skip map() and andThen() calls.
 * I will call the second parameter of match(). I will return the default value for unwrapOr().
 * @template TError
 * @implements IResult<never, TError>
 */
final readonly class Err implements IResult
{
    /**
     * @param TError $error
     * @psalm-internal Tuleap\NeverThrow
     * @psalm-pure
     */
    public function __construct(
        public mixed $error,
    ) {
    }

    /**
     * @return Err<TError>
     * @psalm-mutation-free
     */
    #[\Override]
    public function map(callable $fn): Err
    {
        return new Err($this->error);
    }

    /**
     * @template TNewError
     * @psalm-param callable(TError): TNewError $fn
     * @return Err<TNewError>
     */
    #[\Override]
    public function mapErr(callable $fn): Err
    {
        return new Err($fn($this->error));
    }

    /**
     * @return Err<TError>
     * @psalm-mutation-free
     */
    #[\Override]
    public function andThen(callable $fn): Err
    {
        return new Err($this->error);
    }

    /**
     * @template TNewValue
     * @template TNewError
     * @param callable(TError): (Ok<TNewValue> | Err<TNewError>) $fn
     * @return Ok<TNewValue> | Err<TNewError>
     */
    #[\Override]
    public function orElse(callable $fn): Ok|Err
    {
        return $fn($this->error);
    }

    /**
     * @template TReturn
     * @param callable(TError): TReturn $err_fn
     * @return TReturn
     */
    #[\Override]
    public function match(callable $ok_fn, callable $err_fn): mixed
    {
        return $err_fn($this->error);
    }

    /**
     * @template TDefaultValue
     * @param TDefaultValue $default_value
     * @return TDefaultValue
     * @psalm-mutation-free
     */
    #[\Override]
    public function unwrapOr(mixed $default_value): mixed
    {
        return $default_value;
    }
}
