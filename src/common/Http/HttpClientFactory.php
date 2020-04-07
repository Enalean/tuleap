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

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Adapter\Guzzle6\Client;

class HttpClientFactory
{
    private const TIMEOUT = 5;

    public static function createClient(Plugin ...$plugins): \Psr\Http\Client\ClientInterface
    {
        return self::createClientWithStandardConfig(...$plugins);
    }

    public static function createAsyncClient(Plugin ...$plugins): \Http\Client\HttpAsyncClient
    {
        return self::createClientWithStandardConfig(...$plugins);
    }

    /**
     * This client should only be used for Tuleap internal use to
     * query internal resources. Queries requested by users (e.g. webhooks)
     * MUST NOT use it.
     */
    public static function createClientForInternalTuleapUse(Plugin ...$plugins): \Psr\Http\Client\ClientInterface
    {
        return self::createClientWithConfigForInternalTuleapUse(...$plugins);
    }

    /**
     * This client should only be used for Tuleap internal use to
     * query internal resources. Queries requested by users (e.g. webhooks)
     * MUST NOT use it.
     */
    public static function createAsyncClientForInternalTuleapUse(Plugin ...$plugins): \Http\Client\HttpAsyncClient
    {
        return self::createClientWithConfigForInternalTuleapUse(...$plugins);
    }

    /**
     * @return \Http\Client\HttpAsyncClient&\Psr\Http\Client\ClientInterface
     */
    private static function createClientWithStandardConfig(Plugin ...$plugins)
    {
        return self::createClientWithConfig(
            [
                'timeout' => self::TIMEOUT,
                'proxy'   => \ForgeConfig::get('sys_proxy')
            ],
            ...$plugins
        );
    }

    /**
     * @return \Http\Client\HttpAsyncClient&\Psr\Http\Client\ClientInterface
     */
    private static function createClientWithConfigForInternalTuleapUse(Plugin ...$plugins)
    {
        return self::createClientWithConfig(['timeout' => self::TIMEOUT], ...$plugins);
    }

    /**
     * @return \Psr\Http\Client\ClientInterface&\Http\Client\HttpAsyncClient
     */
    private static function createClientWithConfig(array $config, Plugin ...$plugins)
    {
        $client = Client::createWithConfig($config);

        return new PluginClient(
            $client,
            array_merge([new RedirectPlugin()], $plugins)
        );
    }
}
