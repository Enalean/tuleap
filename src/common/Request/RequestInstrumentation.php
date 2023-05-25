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

use Psr\Log\LoggerInterface;
use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Instrument\Prometheus\Prometheus;

class RequestInstrumentation
{
    #[ConfigKey('Log pages that take more than defined milliseconds to render (default: 5000 for 5 seconds)')]
    #[ConfigKeyInt(5000)]
    public const CONFIG_SLOW_PAGES = 'slow_pages_threshold';

    private const COUNT_NAME = 'http_responses_total';
    private const COUNT_HELP = 'Total number of HTTP request';

    private const DURATION_NAME    = 'http_responses_duration';
    private const DURATION_HELP    = 'Duration of http responses in microseconds';
    private const DURATION_BUCKETS = [0.05, 0.1, 0.2, 0.5, 1, 2, 5, 10, 30];

    public function __construct(private readonly Prometheus $prometheus, private readonly LoggerInterface $logger)
    {
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

    private function incrementCodeRouter(string $code, string $router, DetectedBrowser $detected_browser): void
    {
        $this->prometheus->increment(
            self::COUNT_NAME,
            self::COUNT_HELP,
            [
                'code' => $code,
                'router' => $router,
                'browser' => $detected_browser->getName() ?? 'Not identified',
                'browser_is_outdated' => $detected_browser->isAnOutdatedBrowser() ? 'true' : 'false',
            ]
        );
    }

    private function updateRequestDurationHistogram(string $router): void
    {
        $elapsed_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        if ($elapsed_time >= (\ForgeConfig::get(self::CONFIG_SLOW_PAGES) / 1000)) {
            $this->logger->warning(sprintf('Slow page: %s (%.3fs)', $_SERVER['REQUEST_URI'] ?? 'No REQUEST_URI', $elapsed_time));
        }
        $this->prometheus->histogram(
            self::DURATION_NAME,
            self::DURATION_HELP,
            $elapsed_time,
            ['router' => $router],
            self::DURATION_BUCKETS
        );
    }
}
