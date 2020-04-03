<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Queue;

use Psr\Log\LoggerInterface;
use Tuleap\Queue\Redis\BackOffDelayFailedMessage;
use Tuleap\Redis\ClientFactory as RedisClientFactory;

class QueueFactory
{
    public const REDIS = 'redis';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws NoQueueSystemAvailableException
     */
    public function getPersistentQueue(string $queue_name, string $favor = ''): PersistentQueue
    {
        if (RedisClientFactory::canClientBeBuiltFromForgeConfig()) {
            return new Redis\RedisPersistentQueue(
                $this->logger,
                new BackOffDelayFailedMessage(
                    $this->logger,
                    static function (int $time_to_sleep): void {
                        sleep($time_to_sleep);
                    }
                ),
                $queue_name
            );
        }
        if ($favor === self::REDIS) {
            throw new NoQueueSystemAvailableException();
        }
        return new Noop\PersistentQueue();
    }
}
