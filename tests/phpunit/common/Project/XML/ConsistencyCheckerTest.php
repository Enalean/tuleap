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
 *
 */

namespace Tuleap\Project\XML;

use Mockery as M;
use PHPUnit\Framework\TestCase;
use Service;
use ServiceManager;
use Tuleap\AgileDashboard\AgileDashboardService;
use Tuleap\Git\GitService;

class ConsistencyCheckerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ConsistencyChecker
     */
    private $checker;
    /**
     * @var \EventManager
     */
    private $service_manager;

    protected function setUp(): void
    {
        $this->service_manager = M::mock(ServiceManager::class);
        $this->checker         = new ConsistencyChecker($this->service_manager, new XMLFileContentRetriever());
    }

    public function testAreAllServicesAvailable(): void
    {
        $this->service_manager->shouldReceive('getListOfServicesAvailableAtSiteLevel')->andReturns([
            M::mock(Service::class, ['getShortName' => \trackerPlugin::SERVICE_SHORTNAME]),
            M::mock(Service::class, ['getShortName' => \GitPlugin::SERVICE_SHORTNAME]),
            M::mock(Service::class, ['getShortName' => \AgileDashboardPlugin::PLUGIN_SHORTNAME]),
        ]);

        $this->assertTrue($this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml'));
    }

    public function testAgileDashboardIsNotAvailable(): void
    {
        $this->service_manager->shouldReceive('getListOfServicesAvailableAtSiteLevel')->andReturns([
            M::mock(Service::class, ['getShortName' => \trackerPlugin::SERVICE_SHORTNAME]),
            M::mock(Service::class, ['getShortName' => \GitPlugin::SERVICE_SHORTNAME]),
        ]);

        $this->assertFalse($this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml'));
    }

    public function testInvalidFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures');
    }
}
