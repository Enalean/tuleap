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

namespace Tuleap\Disposable;

use Tuleap\Disposable\Tests\TestDisposable;
use Tuleap\Test\PHPUnit\TestCase;

final class DisposeTest extends TestCase
{
    public function testItReturnsTheValueFromItsCallbackAndDisposes(): void
    {
        $disposable       = new TestDisposable('disposable value');
        $disposable_value = 'not given';

        $return_value = Dispose::using(
            $disposable,
            static function (TestDisposable $param) use (&$disposable_value) {
                $disposable_value = $param->value;
                return 'return value';
            }
        );

        self::assertSame('return value', $return_value);
        self::assertSame('disposable value', $disposable_value);
        self::assertTrue($disposable->dispose_was_called);
    }

    public function testWhenCallbackThrowsItDisposes(): void
    {
        $disposable = new TestDisposable();
        $was_caught = false;
        try {
            Dispose::using($disposable, static function () {
                throw new \LogicException('An error occurred');
            });
        } catch (\LogicException $e) {
            $was_caught = true;
            self::assertSame('An error occurred', $e->getMessage());
            self::assertTrue($disposable->dispose_was_called);
        } finally {
            self::assertTrue($was_caught);
        }
    }

    public function testWhenCallbackTriggersErrorItDisposes(): void
    {
        $disposable = new TestDisposable();
        $was_caught = false;
        try {
            Dispose::using($disposable, static function () {
                trigger_error('Fatal error', E_USER_ERROR);
            });
        } catch (\Throwable) {
            $was_caught = true;
            self::assertTrue($disposable->dispose_was_called);
        } finally {
            self::assertTrue($was_caught);
        }
    }
}
