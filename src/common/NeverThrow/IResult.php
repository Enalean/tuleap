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
 * I hold the result of an operation that could fail. For example, permission to do the operation could be denied.
 * If the operation succeeds, I will hold an `Ok` value. If the operation fails, I will hold an `Err` value.
 * @template TValue
 * @template TError
 * @psalm-internal Tuleap\NeverThrow
 */
interface IResult
{
    /**
     * map applies `$fn` to an `Ok` value, leaving `Err` untouched.
     *
     * If called on an `Ok`, it returns a new `Ok` holding the result of calling `$fn`.
     * It can change the type of the "inner" `Ok` value.
     *
     * If called on an `Err`, it returns the same `Err`.
     *
     * It maps from a `Ok<TValue> | Err<TError>` to a `Ok<TNewValue> | Err<TError>`.
     *
     * `$fn` should _not_ return a new `Ok|Err`. It works on the "inner" type.
     * @template TNewValue
     * @psalm-param pure-callable(TValue): TNewValue $fn
     * @return Ok<TNewValue> | Err<TError>
     */
    public function map(callable $fn): Ok|Err;

    /**
     * mapErr applies `$fn` to an `Err` value, leaving `Ok` untouched.
     *
     * If called on an `Ok`, it returns the same `Ok`.
     *
     * If called on an `Err`, it returns a new `Err` holding the result of calling `$fn`.
     * It can change the type of the "inner" `Err` value.
     *
     * It maps from a `Ok<TValue> | Err<TError>` to a `Ok<TValue> | Err<TNewError>`.
     *
     * `$fn` should _not_ return a new `Ok|Err`. It works on the "inner" type.
     * @template TNewError
     * @psalm-param pure-callable(TError): TNewError $fn
     * @return Ok<TValue> | Err<TNewError>
     */
    public function mapErr(callable $fn): Ok|Err;

    /**
     * andThen applies `$fn` to an `Ok` value, leaving `Err` untouched.
     *
     * It is useful when you need to do a subsequent computation using the inner `Ok` value, but that computation might fail.
     * It allows you to chain calls that could return an error.
     *
     * If called on an `Ok`, it returns the result of calling `$fn`. `$fn` must return a new `Ok|Err`.
     * It can change the type of both the `Ok` value and the `Err` value.
     *
     * If called on an `Err`, it returns the same `Err`.
     *
     * It can change the variant of the result: you can go from an `Ok` to an `Err` if `$fn` returns an `Err` variant.
     *
     * Additionally, andThen can be used to flatten a nested `Ok<Ok<ValueType> | Err<ErrorType> | Err>OtherErrorType>` into a `Ok<ValueType> | Err<ErrorType>`.
     * @template TNewValue
     * @template TNewError
     * @param callable(TValue): (Ok<TNewValue> | Err<TNewError>) $fn
     * @return Ok<TNewValue> | Err<TNewError> | Err<TError>
     */
    public function andThen(callable $fn): Ok|Err;

    /**
     * orElse applies `$fn` to an `Err` value, leaving `Ok` untouched.
     *
     * It is useful when you want to recover from an error and do a computation using the inner `Err` value, but
     * that computation might fail again.
     *
     * If called on an `Ok`, it returns the same `Ok`.
     *
     * If called on an `Err`, it returns the result of calling `$fn`. `$fn` must return a new `Ok|Err`.
     * It can change the type of both the `Ok` value and the `Err` value.
     *
     * Additionally, orElse can be used to flatten a nested `Ok<OtherValueType> | Err<Ok<ValueType> | Err<ErrorType>>` into a `Ok<ValueType> | Err<ErrorType>`.
     * @template TNewValue
     * @template TNewError
     * @param callable(TError): (Ok<TNewValue> | Err<TNewError>) $fn
     * @return Ok<TNewValue> | Ok<TValue> | Err<TNewError>
     */
    public function orElse(callable $fn): Ok|Err;

    /**
     * match applies `$ok_fn` on an `Ok` value, or applies `$err_fn` to an `Err` value.
     * Both callbacks _must_ have the same return type.
     *
     * match is typically called at the end of a `Ok|Err` chain of operations, to deal with
     * both the `Ok` and `Err` cases.
     *
     * You don't need to return another `Ok|Err` (you can return `void`) but you can if you want to.
     * @template TReturn
     * @param callable(TValue): TReturn $ok_fn
     * @param callable(TError): TReturn $err_fn
     * @return TReturn
     */
    public function match(callable $ok_fn, callable $err_fn): mixed;

    /**
     * unwrapOr returns the "inner" `Ok` value or returns `$default_value` if called on `Err`
     *
     * If called on an `Ok`, it returns its "inner" value.
     *
     * If called on an `Err`, it returns `$default_value`.
     * @template DefaultValue
     * @param DefaultValue $default_value
     * @return TValue|DefaultValue
     * @psalm-mutation-free
     */
    public function unwrapOr(mixed $default_value): mixed;
}
