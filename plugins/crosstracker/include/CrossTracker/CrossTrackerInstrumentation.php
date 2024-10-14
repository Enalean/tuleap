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

namespace Tuleap\CrossTracker;

use Tuleap\Instrument\Prometheus\Prometheus;

final readonly class CrossTrackerInstrumentation
{
    private const DURATION_NAME    = 'crosstracker_tql_query_duration';
    private const DURATION_HELP    = 'Duration of CrossTracker TQL query in microseconds';
    private const DURATION_BUCKETS = [0.1, 0.2, 0.5, 1, 2, 5, 10, 30];

    private const TRACKER_COUNT_NAME = 'crosstracker_tql_tracker_count';
    private const TRACKER_COUNT_HELP = 'Number of tracker used by CrossTracker TQL query';

    private const SELECT_COUNT_NAME = 'crosstracker_tql_select_count';
    private const SELECT_COUNT_HELP = 'Number of columns selected by CrossTracker TQL query';

    private const ORDER_BY_COUNT_NAME = 'crosstracker_tql_order_by_used_total';
    private const ORDER_BY_COUNT_HELP = 'Is order by used in CrossTracker TQL query';

    public function __construct(private Prometheus $prometheus)
    {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function updateQueryDuration(callable $callback)
    {
        $start_time      = microtime(true);
        $callback_result = $callback();
        $this->prometheus->histogram(
            self::DURATION_NAME,
            self::DURATION_HELP,
            microtime(true) - $start_time,
            [],
            self::DURATION_BUCKETS,
        );
        return $callback_result;
    }

    public function updateTrackerCount(int $count): void
    {
        $this->prometheus->gaugeSet(
            self::TRACKER_COUNT_NAME,
            self::TRACKER_COUNT_HELP,
            $count,
        );
    }

    public function updateSelectCount(int $count): void
    {
        $this->prometheus->gaugeSet(
            self::SELECT_COUNT_NAME,
            self::SELECT_COUNT_HELP,
            $count,
        );
    }

    public function updateOrderByUsage(): void
    {
        $this->prometheus->increment(
            self::ORDER_BY_COUNT_NAME,
            self::ORDER_BY_COUNT_HELP,
        );
    }
}
