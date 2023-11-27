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

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class OptionTest extends TestCase
{
    public function testCanUnwrapValue(): void
    {
        self::assertSame('value', Option::fromValue('value')->unwrapOr('nothing'));
        self::assertSame('nothing', Option::nothing(\Psl\Type\string())->unwrapOr('nothing'));
    }

    private function getNullable(bool $return_null): ?string
    {
        return ($return_null) ? null : 'value';
    }

    public function testFromNullable(): void
    {
        self::assertSame('value', Option::fromNullable($this->getNullable(false))->unwrapOr('nothing'));
        self::assertSame('nothing', Option::fromNullable($this->getNullable(true))->unwrapOr('nothing'));
    }

    public function testCanApplyWhenValueIsProvided(): void
    {
        $value         = new \stdClass();
        $applied_value = null;

        $optional = Option::fromValue($value);
        $optional->apply(static function (mixed $received_value) use (&$applied_value): void {
            $applied_value = $received_value;
        });

        self::assertSame($applied_value, $value);
        self::assertTrue($optional->isValue());
        self::assertFalse($optional->isNothing());
    }

    public function testDoNoApplyOnNothing(): void
    {
        $has_called_apply_function = false;

        $optional = Option::nothing(\stdClass::class);
        $optional->apply(static function () use (&$has_called_apply_function): void {
            $has_called_apply_function = true;
        });

        self::assertFalse($has_called_apply_function);
        self::assertFalse($optional->isValue());
        self::assertTrue($optional->isNothing());
    }

    public function testValueMapReturnsDifferentValue(): void
    {
        $value             = 66;
        $callback_argument = null;

        $option     = Option::fromValue($value);
        $new_option = $option->map(static function (mixed $received_value) use (&$callback_argument): string {
            $callback_argument = $received_value;
            return 'callback';
        });

        self::assertSame($value, $callback_argument);
        self::assertNotSame($option, $new_option);
        self::assertTrue($option->isValue());
        self::assertSame('callback', $new_option->unwrapOr('default'));
    }

    public function testNothingMapReturnsNothing(): void
    {
        $has_called_map_function = false;

        $option     = Option::nothing(\Psl\Type\string());
        $new_option = $option->map(static function () use (&$has_called_map_function): \stdClass {
            $has_called_map_function = true;
            return new \stdClass();
        });

        self::assertFalse($has_called_map_function);
        self::assertNotSame($option, $new_option);
        self::assertTrue($new_option->isNothing());
    }

    public function testValueMapOrToDifferentValue(): void
    {
        $callback_argument = null;

        $value        = 'initial';
        $mapped_value = Option::fromValue($value)->mapOr(
            static function (mixed $received_value) use (&$callback_argument): string {
                $callback_argument = $received_value;
                return 'callback';
            },
            'default'
        );

        self::assertSame($value, $callback_argument);
        self::assertSame('callback', $mapped_value);
    }

    public function testNothingMapOrToDefault(): void
    {
        $has_called_map_function = false;

        $mapped_value = Option::nothing(\stdClass::class)->mapOr(
            static function () use (&$has_called_map_function): string {
                $has_called_map_function = true;
                return 'unexpected';
            },
            'default'
        );

        self::assertFalse($has_called_map_function);
        self::assertSame('default', $mapped_value);
    }

    public function testValueMapsToOk(): void
    {
        $value  = new \stdClass();
        $result = Option::fromValue($value)->okOr(Result::err('Not expected'));
        self::assertTrue(Result::isOk($result));
        self::assertSame($value, $result->value);
    }

    public function testNothingMapsToErr(): void
    {
        $expected_error = Result::err('Expected error');
        $result         = Option::nothing(\stdClass::class)->okOr($expected_error);
        self::assertSame($expected_error, $result);
    }

    public function testValueAndThenReturnsDifferentOption(): void
    {
        $value             = new \stdClass();
        $callback_argument = null;

        $option     = Option::fromValue($value);
        $new_option = $option->andThen(static function (mixed $received_value) use (&$callback_argument): Option {
            $callback_argument = $received_value;
            return Option::fromValue('callback');
        });

        self::assertSame($value, $callback_argument);
        self::assertNotSame($option, $new_option);
        self::assertTrue($new_option->isValue());
        self::assertSame('callback', $new_option->unwrapOr('default'));
    }

    public function testNothingAndThenReturnsNothing(): void
    {
        $has_called_map_function = false;

        $option     = Option::nothing(\stdClass::class);
        $new_option = $option->andThen(static function () use (&$has_called_map_function): Option {
            $has_called_map_function = true;
            return Option::fromValue(21);
        });

        self::assertFalse($has_called_map_function);
        self::assertNotSame($option, $new_option);
        self::assertTrue($new_option->isNothing());
    }

    public function testMatchValue(): void
    {
        $optional = Option::fromValue(new \stdClass());

        $has_called_match_function         = false;
        $has_called_match_nothing_function = false;

        $optional->match(
            function () use (&$has_called_match_function): void {
                $has_called_match_function = true;
            },
            function () use (&$has_called_match_nothing_function): void {
                $has_called_match_nothing_function = true;
            },
        );

        self::assertTrue($has_called_match_function);
        self::assertFalse($has_called_match_nothing_function);
    }

    public function testMatchNothing(): void
    {
        $optional = Option::nothing(\stdClass::class);

        $has_called_match_function         = false;
        $has_called_match_nothing_function = false;

        $optional->match(
            function () use (&$has_called_match_function): void {
                $has_called_match_function = true;
            },
            function () use (&$has_called_match_nothing_function): void {
                $has_called_match_nothing_function = true;
            },
        );

        self::assertFalse($has_called_match_function);
        self::assertTrue($has_called_match_nothing_function);
    }
}
