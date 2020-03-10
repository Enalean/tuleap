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

declare(strict_types = 1);

class XMLFullStructureExporterTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    private $router;
    /**
     * @var AgileDashboard_XMLFullStructureExporter
     */
    private $xml_exporter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->router = Mockery::spy(\AgileDashboardRouter::class);

        $this->event_manager = Mockery::mock(\EventManager::class);
        $router_builder      = Mockery::spy(\AgileDashboardRouterBuilder::class)
            ->shouldReceive('build')->andReturns($this->router)->getMock();

        $this->xml_exporter = new AgileDashboard_XMLFullStructureExporter(
            $this->event_manager,
            $router_builder
        );

        $this->project = Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns(101)->getMock();
    }

    public function testItAsksToPluginToExportStuffForTheGivenProject(): void
    {
        $this->event_manager->shouldReceive('processEvent')
            ->with(
                AGILEDASHBOARD_EXPORT_XML,
                Mockery::on(
                    function ($params) {
                        return $params['project']->getID() === $this->project->getID() && isset($params['into_xml']);
                    }
                )
            );

        $this->xml_exporter->export($this->project);
    }

    public function testAgileDashboardExportsItself(): void
    {
        $this->router->shouldReceive('route')->once();

        $this->event_manager->shouldReceive('processEvent')->with(AGILEDASHBOARD_EXPORT_XML, Mockery::any());

        $this->xml_exporter->export($this->project);
    }
}
