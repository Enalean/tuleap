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

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Admin\Homepage\NbUsersByStatus;
use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\Http\HTTPFactoryBuilder;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class MetricsControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMetricsAreRendered() : void
    {
        $dao             = Mockery::mock(MetricsCollectorDao::class);
        $nb_user_builder = Mockery::mock(NbUsersByStatusBuilder::class);
        $event_manager   = Mockery::mock(EventManager::class);
        $controller      = new MetricsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            Mockery::mock(EmitterInterface::class),
            $dao,
            $nb_user_builder,
            $event_manager
        );

        $dao->shouldReceive('getProjectsByStatus')->andReturn([]);
        $nb_user_builder->shouldReceive('getNbUsersByStatusBuilder')->andReturn(
            new NbUsersByStatus(0, 0, 0, 0, 0, 0, 0)
        );
        $event_manager->shouldReceive('processEvent');

        $response = $controller->handle(Mockery::mock(ServerRequestInterface::class));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
