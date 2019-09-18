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

use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\Admin\Homepage\UserCounterDao;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\PrometheusMetrics\MetricsAuthentication;
use Tuleap\PrometheusMetrics\MetricsCollectorDao;
use Tuleap\PrometheusMetrics\MetricsController;
use Tuleap\Redis\ClientFactory;
use Tuleap\Redis\RedisConnectionException;
use Tuleap\Request\CollectRoutesEvent;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

class prometheus_metricsPlugin extends Plugin  // @codingStandardsIgnoreLine
{
    public const NAME = 'prometheus_metrics';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-prometheus_metrics', __DIR__.'/../site-content');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\PrometheusMetrics\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function routeGetMetrics(): MetricsController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $redis_client     = null;
        try {
            $redis_client = ClientFactory::fromForgeConfig();
        } catch (\RedisException | RedisConnectionException $exception) {
            // If no redis, client can be null
        }
        return new MetricsController(
            $response_factory,
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter(),
            new MetricsCollectorDao(),
            new NbUsersByStatusBuilder(new UserCounterDao()),
            EventManager::instance(),
            $redis_client,
            new MetricsAuthentication(
                $response_factory,
                $this->getPluginEtcRoot()
            )
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get('/metrics', $this->getRouteHandler('routeGetMetrics'));
    }
}
