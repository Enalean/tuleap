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

use Ramsey\Uuid\Rfc4122\UuidV7;
use Ramsey\Uuid\UuidInterface;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

class DBPersistentQueueDAO extends DataAccessObject
{
    public function saveMessage(
        string $queue_name,
        string $topic,
        string $payload,
        \DateTimeImmutable $current_time,
    ): void {
        $timestamp_microsecond = (int) $current_time->format('u');

        $sql = 'INSERT INTO async_events(id, queue_name, topic, payload, enqueue_timestamp, enqueue_timestamp_microsecond, nb_added_in_queue)
                VALUES (?, ?, ?, ?, ?, ?, 0)';

        $this->getDB()->run(
            $sql,
            UuidV7::uuid7($current_time)->getBytes(),
            $queue_name,
            $topic,
            $payload,
            $current_time->getTimestamp(),
            $timestamp_microsecond
        );
    }

    public function incrementNumberOfProcessingAttemptsOfMessage(UuidInterface $message_id): void
    {
        $this->getDB()->run(
            'UPDATE async_events SET nb_added_in_queue = nb_added_in_queue + 1 WHERE id = ?',
            $message_id->getBytes()
        );
    }

    /**
     * @psalm-return array{id: UuidInterface, topic: string, payload: string, enqueue_timestamp: positive-int, enqueue_timestamp_microsecond: positive-int, nb_added_in_queue: positive-int}|null
     */
    public function retrieveAMessageToProcess(string $queue_name): ?array
    {
        $row = $this->getDB()->row(
            'SELECT id, topic, payload, nb_added_in_queue
            FROM async_events
            WHERE queue_name = ?
            ORDER BY id
            LIMIT 1
            FOR UPDATE SKIP LOCKED',
            $queue_name
        );
        if ($row === null) {
            return null;
        }
        $row['id'] = UuidV7::fromBytes($row['id']);
        return $row;
    }

    public function deleteMessage(UuidInterface $message_id): void
    {
        $this->getDB()->run('DELETE FROM async_events WHERE id = ?', $message_id->getBytes());
    }

    public function getNbMessagesInQueue(string $queue_name): int
    {
        return $this->getDB()->single('SELECT COUNT(id) FROM async_events WHERE queue_name = ?', [$queue_name]);
    }

    /**
     * @psalm-return Option<int>
     */
    public function getEnqueueTimestampOfOldestMessageInQueue(string $queue_name): Option
    {
        $row = $this->getDB()->row('SELECT enqueue_timestamp FROM async_events WHERE queue_name = ? ORDER BY id DESC LIMIT 1', $queue_name);
        return Option::fromNullable($row['enqueue_timestamp'] ?? null);
    }
}
