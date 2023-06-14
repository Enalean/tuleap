<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Option;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

/**
 * @template Value
 */
final class Option
{
    /**
     * @psalm-param Value $value
     */
    private function __construct(
        private mixed $value,
        private bool $has_value,
    ) {
    }

    /**
     * @template T
     * @psalm-param T $value
     * @psalm-return self<T>
     */
    public static function fromValue(mixed $value): self
    {
        return new self($value, true);
    }

    /**
     * @template T
     * @psalm-param class-string<T>|\Psl\Type\TypeInterface<T> $type
     * @psalm-return self<T>
     */
    public static function nothing(string|\Psl\Type\TypeInterface $type): self
    {
        /** @psalm-var self<T> $res */
        $res = new self(null, false);
        return $res;
    }

    /**
     * @psalm-param callable(Value): void $fn
     */
    public function apply(callable $fn): void
    {
        if (! $this->has_value) {
            return;
        }

        $fn($this->value);
    }

    /**
     * @psalm-param callable(Value): void $value_fn
     * @psalm-param callable(): void $nothing_fn
     */
    public function match(callable $value_fn, callable $nothing_fn): void
    {
        if (! $this->has_value) {
            $nothing_fn();
            return;
        }

        $value_fn($this->value);
    }

    /**
     * @template T
     * @psalm-param callable(Value): T $fn
     * @psalm-param T $default
     * @psalm-return T
     */
    public function mapOr(callable $fn, mixed $default): mixed
    {
        if (! $this->has_value) {
            return $default;
        }

        return $fn($this->value);
    }

    /**
     * @template F
     * @psalm-param Err<F> $err
     * @psalm-return Ok<Value>|Err<F>
     */
    public function okOr(Err $err): Ok|Err
    {
        return $this->mapOr(
            /**
             * @psalm-param Value $value
             * @psalm-return Ok<Value>
             */
            fn(mixed $value): Ok => Result::ok($value),
            $err,
        );
    }

    /**
     * @return Value|mixed
     */
    public function unwrapOr(mixed $default)
    {
        if (! $this->has_value) {
            return $default;
        }

        return $this->value;
    }

    public function isValue(): bool
    {
        return $this->has_value;
    }

    public function isNothing(): bool
    {
        return ! $this->has_value;
    }
}
