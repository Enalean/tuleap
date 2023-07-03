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
use Psr\Http\Message\ServerRequestInterface;
use Redis;
use Tuleap\Admin\Homepage\NbUsersByStatus;
use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\BuildVersion\FlavorFinder;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Queue\Worker;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class MetricsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testMetricsAreRendered(): void
    {
        $dao               = $this->createMock(MetricsCollectorDao::class);
        $nb_user_builder   = $this->createMock(NbUsersByStatusBuilder::class);
        $event_manager     = $this->createMock(EventManager::class);
        $redis_client      = $this->getMockBuilder(Redis::class)->onlyMethods(['lLen'])->setConstructorArgs([])->getMock();
        $version_presenter = VersionPresenter::fromFlavorFinder(
            new class implements FlavorFinder {
                public function isEnterprise(): bool
                {
                    return false;
                }
            }
        );
        $controller        = new MetricsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->createMock(EmitterInterface::class),
            $dao,
            $nb_user_builder,
            $event_manager,
            $version_presenter,
            $redis_client,
            $this->createMock(FlushableStorage::class)
        );

        $dao->method('getProjectsByStatus')->willReturn([]);
        $dao->method('getNewSystemEventsCount')->willReturn([]);
        $nb_user_builder->method('getNbUsersByStatusBuilder')->willReturn(
            new NbUsersByStatus(0, 0, 0, 0, 0, 0, 0)
        );
        $event_manager->method('processEvent');

        $redis_client->method('lLen')->with(Worker::EVENT_QUEUE_NAME)->willReturn(0);

        $response = $controller->handle($this->createMock(ServerRequestInterface::class));

        self::assertEquals(200, $response->getStatusCode());
    }
}
