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

namespace Tuleap\Svn;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Backend;
use ForgeConfig;
use System_Command;
use Logger;
use WrapperLogger;
use Exception;

class SvnrootUpdater
{
    const QUEUE_PREFIX = 'tuleap_svnroot_update_';

    const GATEWAY_EXCHANGE  = 'tuleap_svnroot_update_gateway';
    const INTERNAL_EXCHANGE = 'tuleap_svnroot_update_distributor';

    const TOPIC = 'tuleap.svn.svnroot.update';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var AMQPStreamConnection
     */
    private $connection;
    /**
     * @var
     */
    private $channel;

    public function __construct(Logger $logger)
    {
        $this->logger = new WrapperLogger($logger, 'svnroot_updater');
    }

    private function close()
    {
        $this->channel->close();
        $this->connection->close();
    }

    private function connect()
    {
        $this->logger->info("Connect to RabbitMQ server: ".ForgeConfig::get('rabbitmq_server'));
        $this->connection = new AMQPStreamConnection(
            ForgeConfig::get('rabbitmq_server'),
            ForgeConfig::get('rabbitmq_port'),
            ForgeConfig::get('rabbitmq_user'),
            ForgeConfig::get('rabbitmq_password')
        );
        $this->channel = $this->connection->channel();

        $this->wireExchangesForQueuePersistency();

    }

    /**
     * @see http://skillachie.com/2014/06/27/rabbitmq-exchange-to-exchange-bindings-ampq/
     */
    private function wireExchangesForQueuePersistency()
    {
        $this->channel->exchange_declare(self::GATEWAY_EXCHANGE, 'fanout', false, true, false);
        $this->channel->exchange_declare(self::INTERNAL_EXCHANGE, 'topic', false, true, false);
        $this->channel->exchange_bind(self::INTERNAL_EXCHANGE, self::GATEWAY_EXCHANGE);
    }

    public function push()
    {
        $this->connect();

        $this->logger->info('Send message to '.self::TOPIC);
        $message = new AMQPMessage("Update", array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->channel->basic_publish($message, self::GATEWAY_EXCHANGE, self::TOPIC);
        $this->logger->debug('Done');

        $this->close();
    }

    /**
     * @param string $server_id
     */
    public function listen($server_id)
    {
        $this->connect();

        $this->attachQueueToInternalExchange($server_id);

        $this->logger->info("Wait for messages");

        $logger = $this->logger;

        $this->channel->basic_consume(self::QUEUE_PREFIX.$server_id, '', false, false, false, false, function ($msg) use ($logger) {
            try {
                $logger->info("Received ", $msg->body);
                ForgeConfig::set('svn_root_file', '/etc/httpd/conf.d/svnroot.conf');
                ForgeConfig::set('sys_http_user', 'tuleap');
                $backend_svn = Backend::instance('SVN');
                $backend_svn->generateSVNApacheConf();
                $command = new System_Command();
                $command->exec('/sbin/httpd -k graceful');
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $logger->info("Update completed");
            } catch (Exception $e) {
                $logger->error("Caught exception ".get_class($e).": ".$e->getMessage());
            }
        });

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->close();
    }

    /**
     * @param string $server_id
     */
    private function attachQueueToInternalExchange($server_id)
    {
        $queue_is_persistent = true;
        $this->channel->queue_declare(self::QUEUE_PREFIX.$server_id, false, $queue_is_persistent, false, false);
        $this->channel->queue_bind(self::QUEUE_PREFIX.$server_id, self::INTERNAL_EXCHANGE, self::TOPIC);
    }
}
