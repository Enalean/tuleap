<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchMeilisearch\Index\Asynchronous;

use Tuleap\Queue\WorkerEvent;

final class ProcessPendingItemsToIndexTask implements \Tuleap\Queue\QueueTask
{
    private const TOPIC = 'tuleap.fts-meilisearch.process-pending-items-to-index';

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    /**
     * @psalm-param callable():void $index_fn
     */
    public static function runIndexationProcessIfNeeded(WorkerEvent $worker_event, callable $index_fn): void
    {
        if ($worker_event->getEventName() !== self::TOPIC) {
            return;
        }

        $index_fn();
    }

    public function getTopic(): string
    {
        return self::TOPIC;
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getPreEnqueueMessage(): string
    {
        return 'Process pending items for full-text-search indexation';
    }
}
