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

declare(strict_types=1);

namespace Tuleap\Instrument\Prometheus;

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Renderer\RenderTextFormat;
use Enalean\Prometheus\Storage\InMemoryStore;
use Enalean\Prometheus\Storage\NullStore;
use Enalean\Prometheus\Storage\RedisStore;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use ForgeConfig;
use Tuleap\Redis\ClientFactory;

class Prometheus
{
    public const CONFIG_PROMETHEUS_PLATFORM      = 'prometheus_platform';
    public const CONFIG_PROMETHEUS_NODE_EXPORTER = 'prometheus_node_exporter';

    /**
     * @var CollectorRegistry
     */
    private $registry;

    /**
     * @var Prometheus
     */
    private static $instance;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(self::getCollectorRegistry());
        }
        return self::$instance;
    }

    /**
     * @param string[] $labels
     */
    public function increment(string $name, string $help, array $labels = []): void
    {
        $this->incrementBy($name, $help, 1, $labels);
    }

    /**
     * @param array $labels
     */
    public function incrementBy(string $name, string $help, float $count, array $labels = []): void
    {
        [$label_names, $label_values] = $this->getLabelsNamesAndValues($labels);
        $this->registry->getOrRegisterCounter(
            MetricName::fromNamespacedName('tuleap', $name),
            $help,
            MetricLabelNames::fromNames(...$label_names)
        )->incBy($count, ...$label_values);
    }

    /**
     * @param array $labels
     */
    public function gaugeSet(string $name, string $help, float $value, array $labels = []): void
    {
        [$label_names, $label_values] = $this->getLabelsNamesAndValues($labels);
        $this->registry->getOrRegisterGauge(
            MetricName::fromNamespacedName('tuleap', $name),
            $help,
            MetricLabelNames::fromNames(...$label_names)
        )->set($value, ...$label_values);
    }

    /**
     * Histograms allow to instrument things that are distributed across a set of known values like duration or size
     *
     * Given an histogram set with the following buckets (correspond to microseconds of request duration)
     * - 0.05
     * - 0.5
     * - 1
     *
     * I have then 3 requests
     * - 250ms
     * - 750ms
     * - 3s
     *
     * I will get the following results in Prometheus
     * - 0.05: 0  => no requests took less than 50ms
     * - 0.5: 1   => 1 request took less than 500ms
     * - 1: 2     => 2 requests took less than 1s
     * - +Inf: 3  => All requests took less than +Inf
     * - count: 3 => There were 3 requests
     * - sum: 4   => The total of all requests took 4s
     *
     * +Inf, count and sum are automatically generated.
     *
     * Example inspired by @see https://povilasv.me/prometheus-tracking-request-duration/
     *
     * @param array $labels
     * @param float[] $buckets
     */
    public function histogram(string $name, string $help, float $time, array $labels = [], array $buckets = []): void
    {
        [$label_names, $label_values] = $this->getLabelsNamesAndValues($labels);
        $this->registry->getOrRegisterHistogram(
            MetricName::fromNamespacedName('tuleap', $name),
            $help,
            HistogramLabelNames::fromNames(...$label_names),
            $buckets
        )->observe($time, ...$label_values);
    }

    private function getLabelsNamesAndValues(array $labels): array
    {
        $label_names  = [];
        $label_values = [];
        if (ForgeConfig::exists('prometheus_platform')) {
            $label_names  = ['platform'];
            $label_values = [ForgeConfig::get('prometheus_platform')];
        }
        foreach ($labels as $label_name => $label_value) {
            $label_names[]  = $label_name;
            $label_values[] = $label_value;
        }

        return [$label_names, $label_values];
    }

    public function renderText(): string
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    private static function getCollectorRegistry(): CollectorRegistry
    {
        if (
            ClientFactory::canClientBeBuiltFromForgeConfig() &&
            ForgeConfig::exists(self::CONFIG_PROMETHEUS_PLATFORM)
        ) {
            $store = new RedisStore(ClientFactory::fromForgeConfig());
        } else {
            $store = new NullStore();
        }
        return new CollectorRegistry($store);
    }

    public static function getInMemory(): self
    {
        return new self(
            new CollectorRegistry(
                new InMemoryStore()
            )
        );
    }
}
