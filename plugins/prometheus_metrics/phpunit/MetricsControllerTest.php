<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PrometheusMetrics;

use Enalean\Prometheus\Storage\FlushableStorage;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Redis;
use Tuleap\Admin\Homepage\NbUsersByStatus;
use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\BuildVersion\FlavorFinder;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Queue\Worker;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class MetricsControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMetricsAreRendered() : void
    {
        $dao               = Mockery::mock(MetricsCollectorDao::class);
        $nb_user_builder   = Mockery::mock(NbUsersByStatusBuilder::class);
        $event_manager     = Mockery::mock(EventManager::class);
        $redis_client      = Mockery::mock(Redis::class);
        $version_presenter = VersionPresenter::fromFlavorFinder(
            new class implements FlavorFinder {
                public function isEnterprise(): bool
                {
                    return false;
                }
            }
        );
        $controller      = new MetricsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            Mockery::mock(EmitterInterface::class),
            $dao,
            $nb_user_builder,
            $event_manager,
            $version_presenter,
            $redis_client,
            Mockery::mock(FlushableStorage::class)
        );

        $dao->shouldReceive('getProjectsByStatus')->andReturn([]);
        $dao->shouldReceive('getNewSystemEventsCount')->andReturn([]);
        $nb_user_builder->shouldReceive('getNbUsersByStatusBuilder')->andReturn(
            new NbUsersByStatus(0, 0, 0, 0, 0, 0, 0)
        );
        $event_manager->shouldReceive('processEvent');

        $redis_client->shouldReceive('lLen')->with(Worker::EVENT_QUEUE_NAME)->andReturn(0);

        $response = $controller->handle(Mockery::mock(ServerRequestInterface::class));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
