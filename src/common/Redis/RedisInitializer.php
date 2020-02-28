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

class RedisInitializer
{
    public const CONNECT_TIMEOUT = 0.1;

    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var ConcealedString
     */
    private $password;

    public function __construct(string $host, int $port, ConcealedString $password)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->password = $password;
    }

    /**
     * @throws RedisConnectionException
     * @throws \RedisException
     */
    public function init(\Redis $client) : void
    {
        if ($this->host === '') {
            throw new RedisConnectionException('No Redis server has been setup');
        }

        set_error_handler(function ($code, $message) use (&$error_message) {
            $error_message = $message;
        });
        try {
            $is_connected = $client->connect($this->host, $this->port, self::CONNECT_TIMEOUT);
        } finally {
            restore_error_handler();
        }
        if (! $is_connected) {
            throw new RedisConnectionException("Redis connection failed ($error_message)");
        }

        $raw_password     = $this->password->getString();
        $trimmed_password = trim($raw_password);
        \sodium_memzero($raw_password);
        $is_authentication_successful = $trimmed_password !== '' && ! $client->auth($trimmed_password);
        if ($is_authentication_successful) {
            $error_message = trim(preg_replace('/^ERR/', '', $client->getLastError() ?? ''));
            $error_message = str_replace($trimmed_password, '*********pwd*********', $error_message);
            \sodium_memzero($trimmed_password);
            throw new RedisConnectionException("Redis authentication failed ($error_message)");
        }
        \sodium_memzero($trimmed_password);
    }
}
