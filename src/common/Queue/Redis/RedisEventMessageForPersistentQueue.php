<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Queue\Redis;

use JsonException;

/**
 * @psalm-immutable
 */
final class RedisEventMessageForPersistentQueue implements EventMessageForPersistentQueue
{
    private const MESSAGE_FIELD_EVENT_NAME        = 'event_name';
    private const MESSAGE_FIELD_PAYLOAD           = 'payload';
    private const MESSAGE_FIELD_ENQUEUE_TIMESTAMP = '_enqueue_ts';
    private const MESSAGE_FIELD_QUEUED_TOTAL      = '_queued_total';

    /**
     * @var string
     */
    private $topic;
    /**
     * @var mixed
     */
    private $content;
    /**
     * @var float
     */
    private $enqueue_timestamp;
    /**
     * @var int
     */
    private $nb_added_in_queue;

    private function __construct(string $topic, $content, float $enqueue_timestamp, int $nb_added_in_queue)
    {
        $this->topic             = $topic;
        $this->content           = $content;
        $this->enqueue_timestamp = $enqueue_timestamp;
        $this->nb_added_in_queue = $nb_added_in_queue;
    }

    public static function fromTopicAndPayload(string $topic, $content): self
    {
        return new self(
            $topic,
            $content,
            microtime(true),
            0
        );
    }

    public static function fromSerializedEventMessageValue(string $value): self
    {
        try {
            $value_json = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $value_json = [];
        }

        return new self(
            $value_json[self::MESSAGE_FIELD_EVENT_NAME] ?? 'notopic',
            $value_json[self::MESSAGE_FIELD_PAYLOAD] ?? '',
            $value_json[self::MESSAGE_FIELD_ENQUEUE_TIMESTAMP] ?? 0,
            ($value_json[self::MESSAGE_FIELD_QUEUED_TOTAL] ?? 0) + 1
        );
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getPayload()
    {
        return $this->content;
    }

    public function getEnqueueTime(): float
    {
        return $this->enqueue_timestamp;
    }

    public function getNumberOfTimesMessageHasBeenQueued(): int
    {
        return $this->nb_added_in_queue;
    }

    public function toSerializedEventMessageValue(): string
    {
        return json_encode(
            [
                self::MESSAGE_FIELD_EVENT_NAME        => $this->topic,
                self::MESSAGE_FIELD_PAYLOAD           => $this->content,
                self::MESSAGE_FIELD_ENQUEUE_TIMESTAMP => $this->enqueue_timestamp,
                self::MESSAGE_FIELD_QUEUED_TOTAL      => $this->nb_added_in_queue,
            ],
            JSON_THROW_ON_ERROR
        );
    }
}
