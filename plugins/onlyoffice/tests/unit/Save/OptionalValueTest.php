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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\Test\PHPUnit\TestCase;

final class OptionalValueTest extends TestCase
{
    public function testCanApplyWhenValueIsProvided(): void
    {
        $value         = new \stdClass();
        $applied_value = null;

        $optional = OptionalValue::fromValue($value);
        $optional->apply(function (mixed $received_value) use (&$applied_value): void {
            $applied_value = $received_value;
        });

        self::assertSame($applied_value, $value);
    }

    public function testDoNoApplyOnNothing(): void
    {
        $optional = OptionalValue::nothing(\stdClass::class);

        $has_called_apply_function = false;

        $optional->apply(function () use (&$has_called_apply_function): void {
            $has_called_apply_function = true;
        });

        self::assertFalse($has_called_apply_function);
    }

    public function testCanMapOptionalValueWithADefault(): void
    {
        $fn = fn(): string => 'callback';

        self::assertEquals('callback', OptionalValue::fromValue('expected')->mapOr($fn, 'default'));
        self::assertEquals('default', OptionalValue::nothing(\stdClass::class)->mapOr($fn, 'default'));
    }
}
