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
 */

declare(strict_types=1);

namespace Tuleap\Http;

use Http\Adapter\Guzzle7\Client;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Client\ClientInterface;
use Tuleap\DB\ThereIsAnOngoingTransactionChecker;
use Tuleap\Http\Client\FilteredOutboundHTTPResponseAlerter;
use Tuleap\Http\Client\FilteredOutboundHTTPResponseAlerterDAO;
use Tuleap\Http\Client\HTTPOutboundResponseMetricCollector;
use Tuleap\Http\Client\OngoingTransactionCheckerPlugin;
use Tuleap\Http\Client\OutboundHTTPRequestProxy;
use Tuleap\Instrument\Prometheus\Prometheus;

class HttpClientFactory
{
    private const TIMEOUT = 5;

    public static function createClient(Plugin ...$plugins): ClientInterface
    {
        return self::createClientWithStandardConfig(...$plugins);
    }

    public static function createAsyncClient(Plugin ...$plugins): HttpAsyncClient
    {
        return self::createClientWithStandardConfig(...$plugins);
    }

    /**
     * This client should only be used for Tuleap internal use to
     * query internal resources. Queries requested by users (e.g. webhooks)
     * MUST NOT use it.
     */
    public static function createClientForInternalTuleapUse(Plugin ...$plugins): ClientInterface
    {
        return self::createClientWithConfigForInternalTuleapUse(self::TIMEOUT, ...$plugins);
    }

    public static function createClientForInternalTuleapUseWithCustomTimeout(int $timeout, Plugin ...$plugins): HttpAsyncClient&ClientInterface
    {
        return self::createClientWithConfigForInternalTuleapUse($timeout, ...$plugins);
    }

    public static function createClientWithCustomTimeout(int $timeout, Plugin ...$plugins): HttpAsyncClient&ClientInterface
    {
        $config = [
            'timeout' => $timeout,
            'proxy'   => OutboundHTTPRequestProxy::getProxy(),
        ];
        return self::createClientWithConfig(
            $config,
            new HTTPOutboundResponseMetricCollector(Prometheus::instance()),
            new FilteredOutboundHTTPResponseAlerter(\BackendLogger::getDefaultLogger(), new FilteredOutboundHTTPResponseAlerterDAO()),
            ...$plugins,
        );
    }

    private static function createClientWithStandardConfig(Plugin ...$plugins): HttpAsyncClient&ClientInterface
    {
        return self::createClientWithCustomTimeout(
            self::TIMEOUT,
            ...$plugins,
        );
    }

    private static function createClientWithConfigForInternalTuleapUse(int $timeout, Plugin ...$plugins): HttpAsyncClient&ClientInterface
    {
        return self::createClientWithConfig(['timeout' => $timeout], ...$plugins);
    }

    private static function createClientWithConfig(array $config, Plugin ...$plugins): ClientInterface&HttpAsyncClient
    {
        $client = Client::createWithConfig($config);

        return new PluginClient(
            $client,
            array_merge(
                [
                    new OngoingTransactionCheckerPlugin(
                        new ThereIsAnOngoingTransactionChecker(),
                    ),
                    new RedirectPlugin(),
                ],
                $plugins
            )
        );
    }
}
