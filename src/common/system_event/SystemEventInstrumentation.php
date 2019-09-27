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

namespace Tuleap\SystemEvent;

use Tuleap\Instrument\Prometheus\Prometheus;

class SystemEventInstrumentation
{
    private const METRIC_NAME = 'system_events_total';

    private const DURATION_NAME = 'system_events_duration';
    private const DURATION_HELP = 'Duration of system events processing (only processing time, time spent in queue excluded) in seconds';
    private const DURATION_BUCKETS = [1, 2, 5, 10, 30, 60, 120, 300, 600];

    /**
     * @psalm-param value-of<\SystemEvent::ALL_STATUS> $status
     */
    public static function increment(string $status): void
    {
        Prometheus::instance()->increment(self::METRIC_NAME, 'Total number of system events', ['status' => $status]);
    }

    public static function durationHistogram(int $time): void
    {
        if ($time <= 0) {
            return;
        }
        Prometheus::instance()->histogram(
            self::DURATION_NAME,
            self::DURATION_HELP,
            $time,
            [],
            self::DURATION_BUCKETS
        );
    }
}
