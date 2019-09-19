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
 *
 */

namespace Tuleap\Queue\Redis;

use Tuleap\Queue\QueueInstrumentation;
use Tuleap\Redis;
use RedisException;
use Logger;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueServerConnectionException;

/**
 *
 *
 * @see https://stackoverflow.com/questions/27986649/redis-better-way-of-cleaning-the-processing-queuereliable-while-using-brpopl
 */
class RedisPersistentQueue implements PersistentQueue
{
    private const MAX_MESSAGES                    = 1000;
    private const MESSAGE_FIELD_EVENT_NAME        = 'event_name';
    private const MESSAGE_FIELD_PAYLOAD           = 'payload';
    private const MESSAGE_FIELD_ENQUEUE_TIMESTAMP = '_enqueue_ts';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Redis|null
     */
    private $redis;
    private $event_queue_name;

    public function __construct(Logger $logger, $event_queue_name)
    {
        $this->logger           = $logger;
        $this->event_queue_name = $event_queue_name;
    }

    /**
     * @param $worker_id
     * @param $topic
     * @param $callback
     *
     * @throws QueueServerConnectionException|RedisException
     */
    public function listen($worker_id, $topic, $callback)
    {
        $reconnect = false;
        $processing_queue = $this->event_queue_name.'-processing-'.$worker_id;
        do {
            try {
                $this->logger->debug('Connecting to redis server');
                $this->connect();
                $this->logger->debug('Connect OK');
                if ($this->redis->echo("This is Tuleap") !== "This is Tuleap") {
                    throw new QueueServerConnectionException("Unable to echo with redis server");
                }
                $this->logger->debug('Echoed to redis');
                $this->queuePastEvents($this->redis, $processing_queue);
                $this->waitForEvents($this->redis, $processing_queue, $callback);
            } catch (RedisException $e) {
                // we get that due to default_socket_timeout
                if (strtolower($e->getMessage()) === 'read error on connection') {
                    $this->redis = null;
                    $reconnect = true;
                } else {
                    throw $e;
                }
            }
        } while ($reconnect === true);
    }

    /**
     * In case of crash of the worker, the processing queue might contain events not processed yet.
     *
     * This ensure events are re-queued on main event queue before going further
     */
    private function queuePastEvents(\Redis $redis, string $processing_queue) : void
    {
        $this->logger->debug('queuePastEvents');
        do {
            $value = $redis->rpoplpush($processing_queue, $this->event_queue_name);
            if ($value !== false) {
                [$topic,] = $this->getTopicAndEnqueueTimestamp($value);
                QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_REQUEUED);
            }
        } while ($value !== false);
    }

    private function waitForEvents(\Redis $redis, string $processing_queue, callable $callback) : void
    {
        $this->logger->debug('Wait for events');
        $message_counter = 0;
        while ($message_counter < self::MAX_MESSAGES) {
            $value = $redis->brpoplpush($this->event_queue_name, $processing_queue, 0);
            [$topic, $enqueue_time] = $this->getTopicAndEnqueueTimestamp($value);
            QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_DEQUEUED);
            $callback($value);
            $redis->lRem($processing_queue, $value, 1);
            QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_DONE);
            if ($enqueue_time > 0) {
                $elapsed_time = microtime(true) - $enqueue_time;
                QueueInstrumentation::durationHistogram($elapsed_time);
                $this->logger->info(sprintf('Message processed in %.3f seconds [%d/%d]', $elapsed_time, $message_counter, self::MAX_MESSAGES));
            } else {
                $this->logger->info(sprintf('Message processed [%d/%d]', $message_counter, self::MAX_MESSAGES));
            }
            $message_counter++;
        }
        $this->logger->info('Max messages reached');
    }

    /**
     * @throws QueueServerConnectionException
     * @psalm-assert !null $this->redis
     */
    private function connect(): void
    {
        if ($this->redis === null || ! $this->redis->isConnected()) {
            try {
                $this->redis = Redis\ClientFactory::fromForgeConfig();
            } catch (Redis\RedisConnectionException $exception) {
                throw new QueueServerConnectionException($exception->getMessage());
            } catch (RedisException $exception) {
                throw new QueueServerConnectionException($exception->getMessage());
            }
        }
    }

    /**
     * @throws QueueServerConnectionException
     */
    public function pushSinglePersistentMessage($topic, $content)
    {
        $this->connect();
        QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_ENQUEUED);
        $this->redis->lPush(
            $this->event_queue_name,
            json_encode(
                [
                    self::MESSAGE_FIELD_EVENT_NAME        => $topic,
                    self::MESSAGE_FIELD_PAYLOAD           => $content,
                    self::MESSAGE_FIELD_ENQUEUE_TIMESTAMP => microtime(true),
                ]
            )
        );
    }

    private function getTopicAndEnqueueTimestamp(string $value): array
    {
        try {
            $value_json = json_decode($value, true, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            $value_json = [];
        }
        $topic = $value_json[self::MESSAGE_FIELD_EVENT_NAME] ?? 'notopic';
        $enqueue_time = $value_json[self::MESSAGE_FIELD_ENQUEUE_TIMESTAMP] ?? 0;
        return [$topic, $enqueue_time];
    }
}
