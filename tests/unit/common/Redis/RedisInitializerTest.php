<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Redis;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\PHPUnit\TestCase;

final class RedisInitializerTest extends TestCase
{
    public function testRedisConnectionInitialization(): void
    {
        $password    = '              pwd  ';
        $initializer = new RedisInitializer('redis', 6379, new ConcealedString($password));

        $redis = $this->createPartialMock(\Redis::class, [
            'connect',
            'auth',
        ]);
        $redis->expects(self::once())->method('connect')->willReturn(true);
        $redis->expects(self::once())->method('auth')->with('pwd')->willReturn(true);

        $initializer->init($redis);
    }

    public function testInitializationFailsIfNoHostHaveBeenProvided(): void
    {
        $initializer = new RedisInitializer('', 6379, new ConcealedString(''));

        $redis = $this->createPartialMock(\Redis::class, []);

        self::expectException(RedisConnectionException::class);

        $initializer->init($redis);
    }

    public function testInitializationFailsIfConnectDoesNotSucceed(): void
    {
        $initializer = new RedisInitializer('redis', 6379, new ConcealedString(''));

        $redis = $this->createPartialMock(\Redis::class, ['connect']);
        $redis->method('connect')->willReturn(false);

        self::expectException(RedisConnectionException::class);

        $initializer->init($redis);
    }

    public function testNoPasswordLeaksInExceptionMessage(): void
    {
        $password    = 'my_password';
        $initializer = new RedisInitializer('redis', 6379, new ConcealedString($password));

        $redis = $this->createPartialMock(\Redis::class, [
            'connect',
            'auth',
            'getLastError',
        ]);
        $redis->method('connect')->willReturn(true);
        $redis->method('auth')->willReturn(false);
        $redis->expects(self::once())->method('getLastError')->willReturn($password);

        self::expectException(RedisConnectionException::class);

        try {
            $initializer->init($redis);
        } catch (RedisConnectionException $ex) {
            self::assertStringNotContainsStringIgnoringCase($password, $ex->getMessage());
            throw $ex;
        }
    }
}
