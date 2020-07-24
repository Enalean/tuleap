<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\Instrument\Prometheus\Prometheus;

class RequestInstrumentation
{
    private const COUNT_NAME = 'http_responses_total';
    private const COUNT_HELP = 'Total number of HTTP request';

    private const DURATION_NAME    = 'http_responses_duration';
    private const DURATION_HELP    = 'Duration of http responses in microseconds';
    private const DURATION_BUCKETS = [0.05, 0.1, 0.2, 0.5, 1, 2, 5, 10, 30];

    /**
     * @var Prometheus
     */
    private $prometheus;

    public function __construct(Prometheus $prometheus)
    {
        $this->prometheus = $prometheus;
    }

    public function increment(int $code, DetectedBrowser $detected_browser): void
    {
        $this->incrementCodeRouter((string) $code, 'fastroute', $detected_browser);
        $this->updateRequestDurationHistogram('fastroute');
    }

    public function incrementLegacy(DetectedBrowser $detected_browser): void
    {
        $this->incrementCodeRouter('200', 'legacy', $detected_browser);
    }

    public function incrementRest(?int $code, DetectedBrowser $detected_browser): void
    {
        if ($code === null) {
            $code = -1;
        }
        $this->incrementCodeRouter((string) $code, 'rest', $detected_browser);
        $this->updateRequestDurationHistogram('rest');
    }

    /**
     * Soap will also increment legacy router due to pre.php
     * It's not worth fixing it.
     */
    public function incrementSoap(DetectedBrowser $detected_browser): void
    {
        $this->incrementCodeRouter('200', 'soap', $detected_browser);
    }

    private function incrementCodeRouter(string $code, string $router, DetectedBrowser $detected_browser): void
    {
        $this->prometheus->increment(
            self::COUNT_NAME,
            self::COUNT_HELP,
            ['code' => $code, 'router' => $router, 'browser' => $detected_browser->getName() ?? 'Not identified']
        );
    }

    private function updateRequestDurationHistogram(string $router): void
    {
        $this->prometheus->histogram(
            self::DURATION_NAME,
            self::DURATION_HELP,
            microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            ['router' => $router],
            self::DURATION_BUCKETS
        );
    }
}
