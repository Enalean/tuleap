<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Queue;

class QueueInstrumentation
{
    private const METRIC_NAME = 'queue_events_total';

    public const STATUS_ENQUEUED = 'enqueued';
    public const STATUS_DEQUEUED = 'dequeued';
    public const STATUS_REQUEUED = 'requeued';
    public const STATUS_DONE     = 'done';

    private const STATUS_VALUES = [
        self::STATUS_ENQUEUED,
        self::STATUS_DEQUEUED,
        self::STATUS_REQUEUED,
        self::STATUS_DONE,
    ];

    /**
     * @psalm-param value-of<self::STATUS_VALUES> $status
     */
    public static function increment(string $queue, string $topic, string $status): void
    {
        \Tuleap\Instrument\Prometheus\Prometheus::instance()->increment(self::METRIC_NAME, 'Total number of queue events', ['queue' => $queue, 'topic' => $topic, 'status' => $status]);
    }
}
