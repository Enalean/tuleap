<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\SystemEvent;

use SystemEventQueue;
use Tuleap\Event\Dispatchable;

class GetSystemEventQueuesEvent implements Dispatchable
{
    public const NAME = 'getSystemEventQueuesEvent';

    /**
     * @var SystemEventQueue[]
     */
    private $available_queues;

    public function __construct(array $base_queues)
    {
        $this->available_queues = $base_queues;
    }

    public function addAvailableQueue(string $key, SystemEventQueue $queue): void
    {
        $this->available_queues[$key] = $queue;
    }

    /**
     * @return SystemEventQueue[]
     */
    public function getAvailableQueues(): array
    {
        return $this->available_queues;
    }
}
