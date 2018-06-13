<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Instrument;

use ForgeConfig;

/**
 * Wrapper for Dominkl Statsd
 *
 * @see https://github.com/domnikl/statsd-php
 * @see https://matt.aimonetti.net/posts/2013/06/26/practical-guide-to-graphite-monitoring/
 */
class Collect implements StatsdInterface
{
    const CONFIG_PROMETHEUS_PLATFORM = 'prometheus_platform';

    const NAME_SPACE = 'tuleap';

    /**
     * @var \Domnikl\Statsd\Client
     */
    private static $statsd;

    public static function increment($key)
    {
        self::connect();
        self::$statsd->increment($key);
    }

    public static function gauge($key, $value)
    {
        self::connect();
        self::$statsd->gauge($key, $value);
    }

    private static function connect()
    {
        if (self::$statsd === null) {
            if (file_exists('/usr/share/php/statsd/autoload.php') && ForgeConfig::get('statsd_server') != false && ! ForgeConfig::exists(self::CONFIG_PROMETHEUS_PLATFORM)) {
                require_once('/usr/share/php/statsd/autoload.php');
                $connection = new \Domnikl\Statsd\Connection\UdpSocket(ForgeConfig::get('statsd_server'), ForgeConfig::get('statsd_port'));
                $namespace = self::NAME_SPACE;
                $server_id = trim(ForgeConfig::get('statsd_server_id'));
                if ($server_id) {
                    $namespace .= '.'.$server_id;
                }
                self::$statsd = new \Domnikl\Statsd\Client($connection, $namespace);
            } else {
                self::$statsd = new NoopStatsd();
            }
        }
    }
}
