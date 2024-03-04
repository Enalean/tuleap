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

use Tuleap\Queue\PersistentQueueStatistics;
use Tuleap\Queue\QueueInstrumentation;
use Tuleap\Queue\TaskWorker\TaskWorkerTimedOutException;
use Tuleap\Redis;
use RedisException;
use Psr\Log\LoggerInterface;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueServerConnectionException;

/**
 *
 *
 * @see https://stackoverflow.com/questions/27986649/redis-better-way-of-cleaning-the-processing-queuereliable-while-using-brpopl
 */
class RedisPersistentQueue implements PersistentQueue
{
    private const MAX_MESSAGES               = 1000;
    private const MAX_RETRY_PROCESSING_EVENT = 3;

    private ?\Redis $redis = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly BackOffDelayFailedMessage $back_off_delay_failed_message,
        private readonly string $event_queue_name,
    ) {
    }

    /**
     * @throws QueueServerConnectionException|RedisException
     */
    public function listen(string $queue_id, string $topic, callable $callback): void
    {
        $reconnect        = false;
        $processing_queue = $this->event_queue_name . '-processing-' . $queue_id;
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
                if (stripos($e->getMessage(), 'read error on connection') === 0) {
                    $this->redis = null;
                    $reconnect   = true;
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
    private function queuePastEvents(\Redis $redis, string $processing_queue): void
    {
        $this->connect();
        $this->logger->debug('queuePastEvents');
        do {
            $this->redis->watch($processing_queue);

            $potential_events_to_requeue = $this->redis->lRange($processing_queue, 0, -1);
            assert(is_array($potential_events_to_requeue));
            $this->redis->multi();
            foreach ($potential_events_to_requeue as $potential_event_to_requeue) {
                $message = RedisEventMessageForPersistentQueue::fromSerializedEventMessageValue($potential_event_to_requeue);

                if ($message->getNumberOfTimesMessageHasBeenQueued() > self::MAX_RETRY_PROCESSING_EVENT) {
                    $this->logger->debug(
                        sprintf('Discarding message after too many attempts to process it (%s)', $message->toSerializedEventMessageValue())
                    );
                    QueueInstrumentation::increment($this->event_queue_name, $message->getTopic(), QueueInstrumentation::STATUS_DISCARDED);
                    continue;
                }

                $this->back_off_delay_failed_message->delay($message);
                $this->pushMessageIntoEventQueue($message);
                QueueInstrumentation::increment($this->event_queue_name, $message->getTopic(), QueueInstrumentation::STATUS_REQUEUED);
            }
            $this->redis->del($processing_queue);
        } while ($this->redis->exec() === null);
    }

    /**
     * @psalm-param callable(string): void $callback
     */
    private function waitForEvents(\Redis $redis, string $processing_queue, callable $callback): void
    {
        $this->logger->debug('Wait for events');
        $message_counter = 0;
        while ($message_counter < self::MAX_MESSAGES) {
            $value = $redis->brpoplpush($this->event_queue_name, $processing_queue, 0);
            assert(is_string($value));
            $message_metadata = RedisEventMessageForPersistentQueue::fromSerializedEventMessageValue($value);
            $topic            = $message_metadata->getTopic();
            $enqueue_time     = $message_metadata->getEnqueueTime();
            QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_DEQUEUED);
            try {
                $callback($value);
                QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_DONE);
            } catch (TaskWorkerTimedOutException $exception) {
                $this->logger->error($exception->getMessage());
                QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_TIMEDOUT);
            }
            $redis->lRem($processing_queue, $value, 1);
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
     * @throws \JsonException
     */
    public function pushSinglePersistentMessage(string $topic, mixed $content): void
    {
        QueueInstrumentation::increment($this->event_queue_name, $topic, QueueInstrumentation::STATUS_ENQUEUED);
        $this->pushMessageIntoEventQueue(RedisEventMessageForPersistentQueue::fromTopicAndPayload($topic, $content));
    }

    /**
     * @throws QueueServerConnectionException
     * @throws \JsonException
     */
    private function pushMessageIntoEventQueue(RedisEventMessageForPersistentQueue $message_to_queue): void
    {
        $this->connect();
        $this->redis->lPush(
            $this->event_queue_name,
            $message_to_queue->toSerializedEventMessageValue()
        );
    }

    public function getStatistics(): PersistentQueueStatistics
    {
        $this->connect();
        $queue_size = $this->redis->lLen($this->event_queue_name);
        if ($queue_size === false || ! ($queue_size > 0)) {
            return PersistentQueueStatistics::emptyQueue();
        }

        $values = $this->redis->lRange($this->event_queue_name, 0, -1);
        if (! is_array($values) || ! isset($values[0])) {
            return PersistentQueueStatistics::emptyQueue();
        }
        $event_message = RedisEventMessageForPersistentQueue::fromSerializedEventMessageValue($values[0]);

        $enqueue_time = $event_message->getEnqueueTime();
        if ($enqueue_time === 0.0) {
            return PersistentQueueStatistics::emptyQueue();
        }

        $oldest_message = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', $enqueue_time));
        if ($oldest_message === false) {
            return PersistentQueueStatistics::emptyQueue();
        }

        return PersistentQueueStatistics::queueWithMessageToProcess($queue_size, $oldest_message);
    }
}
