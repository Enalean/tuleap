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

use Tuleap\Test\PHPUnit\TestCase;

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
    }

    public function testCanMapOptionalValueWithADefault(): void
    {
        $fn = fn(): string => 'callback';

        self::assertEquals('callback', Option::fromValue('expected')->mapOr($fn, 'default'));
        self::assertEquals('default', Option::nothing(\stdClass::class)->mapOr($fn, 'default'));
    }
}
