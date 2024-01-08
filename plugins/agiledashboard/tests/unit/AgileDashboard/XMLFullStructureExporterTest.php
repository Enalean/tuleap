<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

class XMLFullStructureExporterTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private AgileDashboard_XMLFullStructureExporter $xml_exporter;
    private Project $project;
    private EventManager|\PHPUnit\Framework\MockObject\MockObject $event_manager;
    private AgileDashboardRouter|\PHPUnit\Framework\MockObject\MockObject $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(\AgileDashboardRouter::class);

        $this->event_manager = $this->createMock(\EventManager::class);

        $router_builder = $this->createMock(\AgileDashboardRouterBuilder::class);
        $router_builder->method('build')->willReturn($this->router);

        $this->xml_exporter = new AgileDashboard_XMLFullStructureExporter(
            $this->event_manager,
            $router_builder
        );

        $this->project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();
    }

    public function testItAsksToPluginToExportStuffForTheGivenProject(): void
    {
        $this->router->expects(self::once())->method('route');

        $this->event_manager
            ->expects(self::atLeast(1))
            ->method('processEvent')
            ->willReturnCallback(fn (string $event, array $params) => match (true) {
                $event === AgileDashboard_XMLFullStructureExporter::AGILEDASHBOARD_EXPORT_XML
                    && $params['project']->getID() === $this->project->getID()
                    && isset($params['into_xml']) => true,
            });

        $this->xml_exporter->export($this->project);
    }

    public function testAgileDashboardExportsItself(): void
    {
        $this->router->expects(self::once())->method('route');

        $this->event_manager
            ->method('processEvent')
            ->willReturnCallback(fn (string $event) => match ($event) {
AgileDashboard_XMLFullStructureExporter::AGILEDASHBOARD_EXPORT_XML => true
            });

        $this->xml_exporter->export($this->project);
    }
}
