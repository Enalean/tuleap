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
    const METRIC_NAME = 'http_responses_total';
    const HELP        = 'Total number of HTTP request';

    public static function increment($code)
    {
        self::incrementCodeRouter($code, 'fastroute');
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
        \Tuleap\Instrument\Prometheus\Prometheus::increment(self::METRIC_NAME, self::HELP, ['code' => $code, 'router' => $router]);
    }
}
