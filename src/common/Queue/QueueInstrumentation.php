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

use Tuleap\Instrument\Prometheus\Prometheus;

class QueueInstrumentation
{
    private const METRIC_NAME = 'queue_events_total';

    public const STATUS_ENQUEUED  = 'enqueued';
    public const STATUS_DEQUEUED  = 'dequeued';
    public const STATUS_REQUEUED  = 'requeued';
    public const STATUS_DISCARDED = 'discarded';
    public const STATUS_TIMEDOUT  = 'timedout';
    public const STATUS_DONE      = 'done';

    private const DURATION_NAME = 'queue_events_duration';
    private const DURATION_HELP = 'Duration of background worker events (from enqueue to done) in seconds';
    private const DURATION_BUCKETS = [0.1, 0.5, 1, 2, 5, 10, 20, 60, 120];

    /**
     * @psalm-param self::STATUS_* $status
     */
    public static function increment(string $queue, string $topic, string $status): void
    {
        Prometheus::instance()->increment(self::METRIC_NAME, 'Total number of queue events', ['queue' => $queue, 'topic' => $topic, 'status' => $status]);
    }

    public static function durationHistogram(float $elapsed_time): void
    {
        Prometheus::instance()->histogram(
            self::DURATION_NAME,
            self::DURATION_HELP,
            $elapsed_time,
            [],
            self::DURATION_BUCKETS
        );
    }
}
