<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Queue\DB;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\UUID;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\PersistentQueueStatistics;
use Tuleap\Queue\QueueInstrumentation;
use Tuleap\Queue\TaskWorker\TaskWorkerTimedOutException;
use Tuleap\Queue\WorkerEventContent;

final readonly class DBPersistentQueue implements PersistentQueue
{
    private const MAX_SLEEP_TIME_MICROSEC_WHILE_WAITING_FOR_MESSAGES = 2_000_000;
    private const MAX_RETRY_PROCESSING_EVENT                         = 3;
    private const MAX_MESSAGES                                       = 1000;

    public function __construct(
        private string $queue_name,
        private LoggerInterface $logger,
        private DBPersistentQueueDAO $dao,
        private DBTransactionExecutor $db_transaction_executor,
    ) {
    }

    #[\Override]
    public function pushSinglePersistentMessage(string $topic, mixed $content): void
    {
        $this->dao->saveMessage(
            $this->queue_name,
            $topic,
            \Psl\Json\encode($content),
            new \DateTimeImmutable()
        );
        QueueInstrumentation::increment($this->queue_name, $topic, QueueInstrumentation::STATUS_ENQUEUED);
    }

    #[\Override]
    public function listen(string $queue_id, string $topic, callable $callback): void
    {
        $message_counter = 0;
        while ($message_counter < self::MAX_MESSAGES) {
            $has_something_been_processed = $this->db_transaction_executor->execute(
                function () use ($topic, $callback, $message_counter): bool {
                    $row = $this->dao->retrieveAMessageToProcess($this->queue_name);
                    if ($row === null) {
                        return false;
                    }
                    QueueInstrumentation::increment($this->queue_name, $topic, QueueInstrumentation::STATUS_DEQUEUED);

                    $value_to_send = new WorkerEventContent(
                        $row['topic'],
                        \Psl\Json\decode($row['payload']),
                    );
                    $row_id        = $row['id'];
                    try {
                        $callback($value_to_send);
                        $this->dao->deleteMessage($row_id);
                        QueueInstrumentation::increment($this->queue_name, $topic, QueueInstrumentation::STATUS_DONE);
                    } catch (\Exception $exception) {
                        $this->dealWithProcessingFailure($exception, $value_to_send, $row_id, $row['nb_added_in_queue']);
                    }

                    $elapsed_time = microtime(true) - ((float) sprintf('%d.%d', $row['enqueue_timestamp'], $row['enqueue_timestamp_microsecond']));
                    QueueInstrumentation::durationHistogram($elapsed_time);
                    $this->logger->info(sprintf('Message processed in %.3f seconds [%d/%d]', $elapsed_time, $message_counter, self::MAX_MESSAGES));

                    return true;
                }
            );
            if ($has_something_been_processed) {
                $message_counter++;
            } else {
                usleep(random_int(0, self::MAX_SLEEP_TIME_MICROSEC_WHILE_WAITING_FOR_MESSAGES));
            }
        }
        $this->logger->info('Max messages reached');
    }

    private function dealWithProcessingFailure(
        \Exception $exception,
        WorkerEventContent $worker_event_content,
        UUID $message_id,
        int $current_nb_processing_attempts,
    ): void {
        $this->logger->error(
            sprintf(
                'Failed to successfully process an async event (%s): %s',
                print_r($worker_event_content, true),
                $exception->getMessage()
            ),
            ['exception' => $exception]
        );
        if ($exception instanceof TaskWorkerTimedOutException) {
            QueueInstrumentation::increment($this->queue_name, $worker_event_content->event_name, QueueInstrumentation::STATUS_TIMEDOUT);
        }
        if (($current_nb_processing_attempts + 1) > self::MAX_RETRY_PROCESSING_EVENT) {
            $this->logger->debug(
                sprintf('Discarding message after too many attempts to process it (%s): %s', $worker_event_content->event_name, $worker_event_content->payload)
            );
            $this->dao->deleteMessage($message_id);
            QueueInstrumentation::increment($this->queue_name, $worker_event_content->event_name, QueueInstrumentation::STATUS_DISCARDED);
            return;
        }
        $this->dao->incrementNumberOfProcessingAttemptsOfMessage($message_id);
        QueueInstrumentation::increment($this->queue_name, $worker_event_content->event_name, QueueInstrumentation::STATUS_REQUEUED);
    }

    #[\Override]
    public function getStatistics(): PersistentQueueStatistics
    {
        return $this->db_transaction_executor->execute(
            function (): PersistentQueueStatistics {
                return $this->dao->getEnqueueTimestampOfOldestMessageInQueue($this->queue_name)
                    ->mapOr(
                        function (int $oldest_message_timestamp): PersistentQueueStatistics {
                            $nb_messages = $this->dao->getNbMessagesInQueue($this->queue_name);
                            if ($nb_messages <= 0) {
                                throw new \LogicException(
                                    sprintf('Queue %s seems to be empty but we have found a message in it', $this->queue_name)
                                );
                            }

                            return PersistentQueueStatistics::queueWithMessageToProcess(
                                $nb_messages,
                                new \DateTimeImmutable('@' . $oldest_message_timestamp)
                            );
                        },
                        PersistentQueueStatistics::emptyQueue()
                    );
            }
        );
    }
}
