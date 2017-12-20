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

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @see http://skillachie.com/2014/06/27/rabbitmq-exchange-to-exchange-bindings-ampq/
 */
class ExchangeToExchangeBindings
{
    /**
     * @var AMQPChannel
     */
    private $channel;
    private $publish_exchange_name;
    private $internal_exchange_name;
    private $queue_prefix;

    public function __construct(AMQPChannel $channel, $queue_prefix)
    {
        $this->channel                = $channel;
        $this->queue_prefix           = $queue_prefix;
        $this->publish_exchange_name  = $queue_prefix.'_gateway';
        $this->internal_exchange_name = $queue_prefix.'_distributor';
    }

    public function publish($topic, $content)
    {
        $this->wireExchangesForQueuePersistence();

        $message = new AMQPMessage($content, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

        $this->channel->basic_publish($message, $this->publish_exchange_name, $topic);
    }

    public function addListener($queue_id, $topic, $callback)
    {
        $this->wireExchangesForQueuePersistence();
        $this->addConsumer($queue_id, $topic, $callback);
    }

    private function addConsumer($queue_id, $topic, $callback)
    {
        $queue_name = $this->getConsumerQueue($queue_id);

        $this->attachQueueToInternalExchange($queue_name, $topic);

        $this->channel->basic_consume($queue_name, '', false, false, false, false, $callback);
    }

    private function getConsumerQueue($queue_id)
    {
        return $this->queue_prefix.'_'.$queue_id;
    }

    private function attachQueueToInternalExchange($queue_name, $topic)
    {
        $queue_is_persistent = true;
        $this->channel->queue_declare($queue_name, false, $queue_is_persistent, false, false);
        $this->channel->queue_bind($queue_name, $this->internal_exchange_name, $topic);
    }

    private function wireExchangesForQueuePersistence()
    {
        $this->channel->exchange_declare($this->publish_exchange_name, 'fanout', false, true, false);
        $this->channel->exchange_declare($this->internal_exchange_name, 'topic', false, true, false);
        $this->channel->exchange_bind($this->internal_exchange_name, $this->publish_exchange_name);
    }
}
