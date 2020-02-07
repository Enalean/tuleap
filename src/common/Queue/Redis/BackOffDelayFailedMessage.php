<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Queue\Redis;

use Psr\Log\LoggerInterface;

final class BackOffDelayFailedMessage
{
    private const BASE_DELAY_SEC       = 5;
    private const MAX_RETRIES_EXPONENT = 3;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var callable
     * @psalm-var callable(int):void $delay_function
     */
    private $delay_function;

    /**
     * @psalm-param callable(int):void $delay_function
     */
    public function __construct(LoggerInterface $logger, callable $delay_function)
    {
        $this->logger         = $logger;
        $this->delay_function = $delay_function;
    }

    public function delay(EventMessageForPersistentQueue $message): void
    {
        if ($message->getNumberOfTimesMessageHasBeenQueued() <= 0) {
            throw new CannotDelayMessageThatHasNotBeenQueuedException($message);
        }

        $time_to_sleep = $this->computeTimeToSleep($message->getNumberOfTimesMessageHasBeenQueued());
        $this->logger->debug(sprintf('Delaying by %d seconds message on topic %s', $time_to_sleep, $message->getTopic()));
        ($this->delay_function)(($time_to_sleep));
    }

    private function computeTimeToSleep(int $nb_time_message_has_been_queued): int
    {
        $exponent = min($nb_time_message_has_been_queued - 1, self::MAX_RETRIES_EXPONENT);

        return self::BASE_DELAY_SEC * (2 ** $exponent);
    }
}
