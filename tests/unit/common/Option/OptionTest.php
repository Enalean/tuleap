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
use function Psl\Type\string;

final class OptionTest extends TestCase
{
    public function testCanApplyWhenValueIsProvided(): void
    {
        $value         = new \stdClass();
        $applied_value = null;

        $optional = Option::fromValue($value);
        $optional->apply(function (mixed $received_value) use (&$applied_value): void {
            $applied_value = $received_value;
        });

        self::assertSame($applied_value, $value);
        self::assertTrue($optional->isValue());
        self::assertFalse($optional->isNothing());
        $result = $optional->okOr(Result::err('Not expected'));
        self::assertTrue(Result::isOk($result));
        self::assertSame($value, $result->value);
    }

    public function testDoNoApplyOnNothing(): void
    {
        $optional = Option::nothing(\stdClass::class);

        $has_called_apply_function = false;

        $optional->apply(function () use (&$has_called_apply_function): void {
            $has_called_apply_function = true;
        });

        self::assertFalse($has_called_apply_function);
        self::assertFalse($optional->isValue());
        self::assertTrue($optional->isNothing());
        $expected_error = Result::err('Expected error');
        $result         = $optional->okOr($expected_error);
        self::assertSame($expected_error, $result);
    }

    public function testCanMapOptionalValueWithADefault(): void
    {
        $fn = fn(): string => 'callback';

        self::assertEquals('callback', Option::fromValue('expected')->mapOr($fn, 'default'));
        self::assertEquals('default', Option::nothing(\stdClass::class)->mapOr($fn, 'default'));
    }

    public function testCanUnwrapValue(): void
    {
        self::assertEquals('value', Option::fromValue('value')->unwrapOr('nothing'));
        self::assertEquals('nothing', Option::nothing(string())->unwrapOr('nothing'));
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
