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

declare(strict_types=1);

namespace Tuleap\Project\XML;

use PHPUnit\Framework\MockObject\MockObject;

final class ConsistencyCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ServiceEnableForXmlImportRetriever&MockObject $event;
    private ConsistencyChecker $checker;
    private \EventManager&MockObject $event_manager;
    private \PluginFactory&MockObject $plugin_factory;

    protected function setUp(): void
    {
        $this->event_manager = $this->createMock(\EventManager::class);
        $this->event         = $this->createMock(ServiceEnableForXmlImportRetriever::class);
        $this->event->method('addServiceByName');

        $this->plugin_factory = $this->createMock(\PluginFactory::class);

        $this->checker = new ConsistencyChecker(
            new XMLFileContentRetriever(),
            $this->event_manager,
            $this->event,
            $this->plugin_factory
        );
    }

    public function testAreAllServicesAvailable(): void
    {
        $this->event_manager->method('processEvent')->with($this->event);
        $this->event->method('getAvailableServices')->willReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME       => true,
                \GitPlugin::SERVICE_SHORTNAME           => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true,
            ]
        );

        self::assertTrue($this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml', []));
    }

    public function testAllServicesAreAvailableButNotExtraPlugin(): void
    {
        $this->event_manager->method('processEvent')->with($this->event);
        $this->event->method('getAvailableServices')->willReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME       => true,
                \GitPlugin::SERVICE_SHORTNAME           => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true,
            ]
        );

        $plugin = new \Plugin();
        $this->plugin_factory
            ->method('getPluginByName')
            ->with('graphontrackersv5')
            ->willReturn($plugin);
        $this->plugin_factory
            ->method('isPluginEnabled')
            ->with($plugin)
            ->willReturn(false);

        self::assertFalse(
            $this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml', ['graphontrackersv5'])
        );
    }

    public function testAllServicesAreAvailableButExtraIsNotActivated(): void
    {
        $this->event_manager->method('processEvent')->with($this->event);
        $this->event->method('getAvailableServices')->willReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME       => true,
                \GitPlugin::SERVICE_SHORTNAME           => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true,
            ]
        );

        $this->plugin_factory
            ->method('getPluginByName')
            ->willReturn(null);

        self::assertFalse(
            $this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml', ['graphontrackersv5'])
        );
    }

    public function testAllServicesAndExtraPluginsAreAvailable(): void
    {
        $this->event_manager->method('processEvent')->with($this->event);
        $this->event->method('getAvailableServices')->willReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME       => true,
                \GitPlugin::SERVICE_SHORTNAME           => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true,
            ]
        );

        $plugin = new \Plugin();
        $this->plugin_factory
            ->method('getPluginByName')
            ->with('graphontrackersv5')
            ->willReturn($plugin);
        $this->plugin_factory
            ->method('isPluginEnabled')
            ->with($plugin)
            ->willReturn(true);

        self::assertTrue(
            $this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml', ['graphontrackersv5'])
        );
    }

    public function testAgileDashboardIsNotAvailable(): void
    {
        $this->event_manager->method('processEvent')->with($this->event);
        $this->event->method('getAvailableServices')->willReturn(
            [
                \trackerPlugin::SERVICE_SHORTNAME => true,
                \GitPlugin::SERVICE_SHORTNAME     => true,
            ]
        );

        self::assertFalse($this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures/project.xml', []));
    }

    public function testInvalidFile(): void
    {
        self::expectException(\RuntimeException::class);
        $this->checker->areAllServicesAvailable(__DIR__ . '/_fixtures', []);
    }
}
