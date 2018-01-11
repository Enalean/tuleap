<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Logger;
use Tuleap\Event\Dispatchable;
use PhpAmqpLib\Channel\AMQPChannel;
use Tuleap\System\DaemonLocker;

class WorkerGetQueue implements Dispatchable
{
    const NAME = 'workerGetQueue';

    const MAX_MESSAGES = 1000;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var DaemonLocker
     */
    private $locker;

    private $message_counter = 0;

    public function __construct(Logger $logger, DaemonLocker $locker, AMQPChannel $channel)
    {
        $this->logger  = $logger;
        $this->locker  = $locker;
        $this->channel = $channel;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function incrementMessagesProcessed()
    {
        $this->message_counter++;
        $this->logger->info("Message processed [{$this->message_counter}/".self::MAX_MESSAGES."]");
        if ($this->message_counter >= self::MAX_MESSAGES) {
            $this->logger->info("Max messages reached, exiting...");
            $this->locker->cleanExit();
        }
    }
}
