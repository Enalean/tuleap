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

namespace Tuleap\Instrument\Prometheus;

use ForgeConfig;
use Prometheus\CollectorRegistry;

class Prometheus
{
    const CONFIG_PROMETHEUS_PLATFORM = 'prometheus_platform';

    private static $registry;

    /**
     * @param string $name
     * @param string $help
     * @param array $labels
     */
    public static function increment($name, $help, array $labels = [])
    {
        list($label_names, $label_values) = self::getLabelsNamesAndValues($labels);
        self::get()->getOrRegisterCounter('tuleap', $name, $help, $label_names)->inc($label_values);
    }

    /**
     * @param string $name
     * @param string $help
     * @param float $value
     * @param array $labels
     */
    public static function gaugeSet($name, $help, $value, array $labels = [])
    {
        list($label_names, $label_values) = self::getLabelsNamesAndValues($labels);
        self::get()->getOrRegisterGauge('tuleap', $name, $help, $label_names)->set($value, $label_values);
    }

    private static function getLabelsNamesAndValues(array $labels)
    {
        $label_names  = [];
        $label_values = [];
        if (\ForgeConfig::exists('prometheus_platform')) {
            $label_names  = ['platform'];
            $label_values = [\ForgeConfig::get('prometheus_platform')];
        }
        foreach ($labels as $label_name => $label_value) {
            $label_names[]  = $label_name;
            $label_values[] = $label_value;
        }

        return [$label_names, $label_values];
    }

    public static function get()
    {
        if (self::$registry === null) {
            if (class_exists('Redis') &&
                ForgeConfig::exists('redis_server') &&
                ForgeConfig::exists(self::CONFIG_PROMETHEUS_PLATFORM)) {
                \Prometheus\Storage\Redis::setDefaultOptions(
                    [
                        'host'     => ForgeConfig::get('redis_server'),
                        'port'     => ForgeConfig::get('redis_port'),
                        'password' => ForgeConfig::get('redis_password'),
                    ]
                );
                $adapter = new \Prometheus\Storage\Redis();
            } else {
                $adapter = new \Prometheus\Storage\InMemory();
            }
            self::$registry = new CollectorRegistry($adapter);
        }
        return self::$registry;
    }
}
