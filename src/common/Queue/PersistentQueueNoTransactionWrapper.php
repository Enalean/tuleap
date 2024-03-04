<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Tuleap\DB\CheckThereIsAnOngoingTransaction;

final class PersistentQueueNoTransactionWrapper implements PersistentQueue
{
    public function __construct(
        private readonly PersistentQueue $queue,
        private readonly CheckThereIsAnOngoingTransaction $transaction_checker,
    ) {
    }

    public function pushSinglePersistentMessage(string $topic, mixed $content): void
    {
        $this->transaction_checker->checkNoOngoingTransaction();

        $this->queue->pushSinglePersistentMessage($topic, $content);
    }

    public function listen(string $queue_id, string $topic, callable $callback): void
    {
        $this->queue->listen($queue_id, $topic, $callback);
    }

    public function getStatistics(): PersistentQueueStatistics
    {
        return $this->queue->getStatistics();
    }
}
