<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use ForgeConfig;
use Exception;

class RabbitMQManager
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var AMQPStreamConnection
     */
    private $connection;
    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function connect()
    {
        $this->logger->info('Connect to RabbitMQ server: '.ForgeConfig::get('rabbitmq_server'));
        $this->connection = new AMQPStreamConnection(
            ForgeConfig::get('rabbitmq_server'),
            ForgeConfig::get('rabbitmq_port'),
            ForgeConfig::get('rabbitmq_user'),
            ForgeConfig::get('rabbitmq_password')
        );
        $this->channel = $this->connection->channel();
        return $this->channel;
    }

    public function wait()
    {
        try {
            while (count($this->channel->callbacks)) {
                $this->channel->wait();
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
        $this->close();
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
