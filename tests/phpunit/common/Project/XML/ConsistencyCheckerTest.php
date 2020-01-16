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

class ConsistencyCheckerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $event;

    /**
     * @var ConsistencyChecker
     */
    private $checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\EventManager
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->event_manager = M::mock(\EventManager::class);
        $this->event         = M::mock(ServiceEnableForXmlImportRetriever::class);
        $this->event->shouldReceive('addServiceByName');

        $this->checker = new ConsistencyChecker(new XMLFileContentRetriever(), $this->event_manager, $this->event);
    }

    public function testAreAllServicesAvailable(): void
    {
        $this->event_manager->shouldReceive('processEvent')->withArgs([$this->event]);
        $this->event->shouldReceive('getAvailableServices')->andReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME       => true,
                \GitPlugin::SERVICE_SHORTNAME           => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true
            ]
        );

        $this->assertTrue($this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml'));
    }

    public function testAgileDashboardIsNotAvailable(): void
    {
        $this->event_manager->shouldReceive('processEvent')->withArgs([$this->event]);
        $this->event->shouldReceive('getAvailableServices')->andReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME => true,
                \GitPlugin::SERVICE_SHORTNAME     => true
            ]
        );

        $this->assertFalse($this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml'));
    }

    public function testInvalidFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures');
    }
}
