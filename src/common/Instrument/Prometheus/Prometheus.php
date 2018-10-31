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
use Prometheus\RenderTextFormat;

class Prometheus
{
    const CONFIG_PROMETHEUS_PLATFORM      = 'prometheus_platform';
    const CONFIG_PROMETHEUS_NODE_EXPORTER = 'prometheus_node_exporter';

    private $registry;

    /**
     * @var Prometheus
     */
    private static $instance;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self(self::getCollectorRegistry());
        }
        return self::$instance;
    }

    /**
     * @param string $name
     * @param string $help
     * @param array $labels
     */
    public function increment($name, $help, array $labels = [])
    {
        list($label_names, $label_values) = $this->getLabelsNamesAndValues($labels);
        $this->registry->getOrRegisterCounter('tuleap', $name, $help, $label_names)->inc($label_values);
    }

    /**
     * @param string $name
     * @param string $help
     * @param float $value
     * @param array $labels
     */
    public function gaugeSet($name, $help, $value, array $labels = [])
    {
        list($label_names, $label_values) = $this->getLabelsNamesAndValues($labels);
        $this->registry->getOrRegisterGauge('tuleap', $name, $help, $label_names)->set($value, $label_values);
    }

    private function getLabelsNamesAndValues(array $labels)
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

    public function renderText()
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    private static function getCollectorRegistry()
    {
        if (ForgeConfig::exists('redis_server') &&
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
        return new CollectorRegistry($adapter);
    }

    /**
     * @return Prometheus
     */
    public static function getInMemory()
    {
        return new self(
            new CollectorRegistry(
                new \Prometheus\Storage\InMemory()
            )
        );
    }
}
