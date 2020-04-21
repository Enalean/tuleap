<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Http\Server;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\ServiceAccessEvent;

final class ServiceInstrumentationMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->event_manager = \Mockery::mock(\EventManager::class);
        \EventManager::setInstance($this->event_manager);
    }

    protected function tearDown(): void
    {
        \EventManager::clearInstance();
    }

    public function testAccessIsRegistered(): void
    {
        $middleware = new ServiceInstrumentationMiddleware('service');

        $this->event_manager->shouldReceive('processEvent')->with(\Mockery::type(ServiceAccessEvent::class));

        $middleware->process(new NullServerRequest(), new AlwaysSuccessfulRequestHandler(HTTPFactoryBuilder::responseFactory()));
    }
}
