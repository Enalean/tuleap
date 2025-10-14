<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final class QueueSupervisor
{
    private const int ACCEPTABLE_PROCESS_DELAY = 120;

    public function __construct(private PersistentQueue $queue, private LoggerInterface $logger)
    {
    }

    public function warnWhenThereIsTooMuchDelayInWorkerEventsProcessing(DateTimeImmutable $current_time): void
    {
        $statistics = $this->queue->getStatistics();

        if ($statistics->size <= 0 || $statistics->oldest_message === null) {
            return;
        }

        $current_delay = $current_time->getTimestamp() -  $statistics->oldest_message->getTimestamp();

        if ($current_delay > self::ACCEPTABLE_PROCESS_DELAY) {
            $this->logger->warning(sprintf('There are %d async events waiting to be processed, you should check if Tuleap Workers are running.', $statistics->size));
        }
    }
}
