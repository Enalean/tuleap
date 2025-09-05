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
use Tuleap\Search\ItemToIndex;

/**
 * @psalm-immutable
 */
final class IndexItemTask implements \Tuleap\Queue\QueueTask
{
    private const TOPIC = 'tuleap.fts.index-item';

    private function __construct(private ItemToIndex $item_to_index)
    {
    }

    public static function fromItemToIndex(ItemToIndex $item_to_index): self
    {
        return new self($item_to_index);
    }

    public static function parseWorkerEventIntoItemToIndexWhenPossible(WorkerEvent $worker_event): ?ItemToIndex
    {
        if ($worker_event->getEventName() !== self::TOPIC) {
            return null;
        }

        $payload = $worker_event->getPayload();
        if (
            ! is_array($payload) ||
            ! isset($payload['type'], $payload['content'], $payload['metadata']) ||
            ! array_key_exists('project_id', $payload) ||
            ! is_string($payload['type']) ||
            ! ($payload['project_id'] === null || is_int($payload['project_id'])) ||
            ! is_string($payload['content']) ||
            ! is_array($payload['metadata']) ||
            count($payload['metadata']) === 0 ||
            (isset($payload['content_type']) && ! in_array($payload['content_type'], ItemToIndex::ALL_CONTENT_TYPES, true))
        ) {
            $worker_event->getLogger()->warning(
                sprintf('Got an event %s with an unexpected payload (%s)', self::TOPIC, var_export($payload, true))
            );
            return null;
        }

        return new ItemToIndex($payload['type'], $payload['project_id'], $payload['content'], $payload['content_type'] ?? ItemToIndex::CONTENT_TYPE_PLAINTEXT, $payload['metadata']);
    }

    #[\Override]
    public function getTopic(): string
    {
        return self::TOPIC;
    }

    #[\Override]
    public function getPayload(): array
    {
        return json_decode(json_encode($this->item_to_index, JSON_THROW_ON_ERROR), true, 3, JSON_THROW_ON_ERROR);
    }

    #[\Override]
    public function getPreEnqueueMessage(): string
    {
        return 'Index item ' . $this->item_to_index->type . ' (' .  var_export($this->item_to_index->metadata, true) . ')';
    }
}
