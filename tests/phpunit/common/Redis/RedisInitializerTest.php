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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;

class RedisInitializerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRedisConnectionInitialization()
    {
        $password    = '              pwd  ';
        $initializer = new RedisInitializer('redis', 6379, new ConcealedString($password));

        $redis = \Mockery::mock(\Redis::class);
        $redis->shouldReceive('connect')->once()->andReturns(true);
        $redis->shouldReceive('auth')->once()->with('pwd')->andReturns(true);

        $initializer->init($redis);
    }

    public function testInitializationFailsIfNoHostHaveBeenProvided(): void
    {
        $initializer = new RedisInitializer('', 6379, new ConcealedString(''));

        $redis = \Mockery::mock(\Redis::class);

        $this->expectException(RedisConnectionException::class);

        $initializer->init($redis);
    }

    public function testInitializationFailsIfConnectDoesNotSucceed(): void
    {
        $initializer = new RedisInitializer('redis', 6379, new ConcealedString(''));

        $redis = \Mockery::mock(\Redis::class);
        $redis->shouldReceive('connect')->andReturns(false);

        $this->expectException(RedisConnectionException::class);

        $initializer->init($redis);
    }

    public function testNoPasswordLeaksInExceptionMessage(): void
    {
        $password    = 'my_password';
        $initializer = new RedisInitializer('redis', 6379, new ConcealedString($password));

        $redis = \Mockery::mock(\Redis::class);
        $redis->shouldReceive('connect')->andReturns(true);
        $redis->shouldReceive('auth')->andReturns(false);
        $redis->shouldReceive('getLastError')->once()->andReturns($password);

        $this->expectException(RedisConnectionException::class);

        try {
            $initializer->init($redis);
        } catch (RedisConnectionException $ex) {
            $this->assertStringNotContainsStringIgnoringCase($password, $ex->getMessage());
            throw $ex;
        }
    }
}
