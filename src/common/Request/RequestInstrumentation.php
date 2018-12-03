<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Request;

class RequestInstrumentation
{
    const COUNT_NAME = 'http_responses_total';
    const COUNT_HELP = 'Total number of HTTP request';

    const DURATION_NAME    = 'http_responses_duration';
    const DURATION_HELP    = 'Duration of http responses in microseconds';
    const DURATION_BUCKETS = [0.05, 0.1, 0.2, 0.5, 1, 2, 5, 10, 30];

    public static function increment($code)
    {
        self::incrementCodeRouter($code, 'fastroute');
        self::updateRequestDurationHistogram('fastroute');
    }

    public static function incrementLegacy()
    {
        self::incrementCodeRouter(200, 'legacy');
    }

    public static function incrementRest($code)
    {
        if ($code === null) {
            $code = -1;
        }
        self::incrementCodeRouter($code, 'rest');
        self::updateRequestDurationHistogram('rest');
    }

    /**
     * Soap will also increment legacy router due to pre.php
     * It's not worth fixing it.
     */
    public static function incrementSoap()
    {
        self::incrementCodeRouter(200, 'soap');
    }

    private static function incrementCodeRouter($code, $router)
    {
        $prom = \Tuleap\Instrument\Prometheus\Prometheus::instance();
        $prom->increment(self::COUNT_NAME, self::COUNT_HELP, ['code' => $code, 'router' => $router]);
    }

    private static function updateRequestDurationHistogram($router)
    {
        $prom = \Tuleap\Instrument\Prometheus\Prometheus::instance();
        $prom->histogram(
            self::DURATION_NAME,
            self::DURATION_HELP,
            microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            ['router' => $router],
            self::DURATION_BUCKETS
        );
    }
}
