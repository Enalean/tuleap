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

namespace Tuleap\Queue\RabbitMQ;

class PersistentQueue implements \Tuleap\Queue\PersistentQueue
{
    /**
     * @var RabbitMQManager
     */
    private $manager;
    private $queue_prefix;

    public function __construct(RabbitMQManager $manager, $queue_prefix)
    {
        $this->manager      = $manager;
        $this->queue_prefix = $queue_prefix;
    }

    public function pushSinglePersistentMessage($topic, $content)
    {
        $channel = $this->manager->connect();

        $stuff_queue = new ExchangeToExchangeBindings($channel, $this->queue_prefix);
        $stuff_queue->publish($topic, $content);

        $this->manager->close();
    }

    public function listen($queue_id, $topic, $callback)
    {
        $channel = $this->manager->connect();

        $stuff_queue = new ExchangeToExchangeBindings($channel, $this->queue_prefix);
        $stuff_queue->addListener($queue_id, $topic, $callback);

        $this->manager->wait();
        $this->manager->close();
    }
}
