<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchCommon\Index\Asynchronous;

use Tuleap\Queue\WorkerEvent;
use Tuleap\Search\IndexedItemsToRemove;

final class RemoveItemsFromIndexTask implements \Tuleap\Queue\QueueTask
{
    private const TOPIC = 'tuleap.fts.remove-items-index';

    private function __construct(private IndexedItemsToRemove $indexed_items_to_remove)
    {
    }

    public static function fromItemsToRemove(IndexedItemsToRemove $indexed_items_to_remove): self
    {
        return new self($indexed_items_to_remove);
    }

    public static function parseWorkerEventIntoItemsToRemoveWhenPossible(WorkerEvent $worker_event): ?IndexedItemsToRemove
    {
        if ($worker_event->getEventName() !== self::TOPIC) {
            return null;
        }

        $payload = $worker_event->getPayload();
        if (
            ! isset($payload['type'], $payload['metadata']) ||
            ! is_string($payload['type']) ||
            ! is_array($payload['metadata']) ||
            count($payload['metadata']) === 0
        ) {
            $worker_event->getLogger()->warning(
                sprintf('Got an event %s with an unexpected payload (%s)', self::TOPIC, var_export($payload, true))
            );
            return null;
        }

        return new IndexedItemsToRemove($payload['type'], $payload['metadata']);
    }

    #[\Override]
    public function getTopic(): string
    {
        return self::TOPIC;
    }

    #[\Override]
    public function getPayload(): array
    {
        return json_decode(json_encode($this->indexed_items_to_remove, JSON_THROW_ON_ERROR), true, 3, JSON_THROW_ON_ERROR);
    }

    #[\Override]
    public function getPreEnqueueMessage(): string
    {
        return 'Remove some indexed items ' . $this->indexed_items_to_remove->type . ' (' .  var_export($this->indexed_items_to_remove->metadata, true) . ')';
    }
}
