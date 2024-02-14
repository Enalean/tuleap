<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Request;

use Tuleap\Test\PHPUnit\TestCase;

final class RequestTimeTest extends TestCase
{
    private const REQUEST_TIMESTAMP = 1234567890;

    private mixed $request_time;

    protected function setUp(): void
    {
        $this->request_time      = $_SERVER['REQUEST_TIME'];
        $_SERVER['REQUEST_TIME'] = self::REQUEST_TIMESTAMP;
    }

    protected function tearDown(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->request_time;
    }

    public function testItReturnsTheRequestTimeFromServerSuperGlobal(): void
    {
        self::assertSame(
            self::REQUEST_TIMESTAMP,
            RequestTime::getTimestamp(),
        );
    }

    public function testItReturnsTheCurrentTimeIfServerSuperGlobalIsNotSet(): void
    {
        unset($_SERVER['REQUEST_TIME']);

        self::assertGreaterThan(
            self::REQUEST_TIMESTAMP,
            RequestTime::getTimestamp(),
        );
    }
}
