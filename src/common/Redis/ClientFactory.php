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

namespace Tuleap\Redis;

use Tuleap\Cryptography\ConcealedString;

class ClientFactory
{
    public static function canClientBeBuiltFromForgeConfig()
    {
        return \ForgeConfig::exists('redis_server');
    }

    /**
     * @return \Redis
     * @throws \RedisException
     * @throws RedisConnectionException
     */
    public static function fromForgeConfig()
    {
        $host     = (string) \ForgeConfig::get('redis_server', '');
        $port     = (int) \ForgeConfig::get('redis_port', 6379);
        $password = (string) \ForgeConfig::get('redis_password');

        $redis_initializer = new RedisInitializer($host, $port, new ConcealedString($password));

        $client = new \Redis();
        $redis_initializer->init($client);

        return $client;
    }
}
