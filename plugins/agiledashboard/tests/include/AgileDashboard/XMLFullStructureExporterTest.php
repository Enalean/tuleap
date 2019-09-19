<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class XMLFullStructureExporterTest extends TuleapTestCase
{

    private $event_manager;
    private $router_builder;
    private $router;
    /**
     * @var AgileDashboard_XMLFullStructureExporter
     */
    private $xml_exporter;
    private $project;

    public function setUp()
    {
        $this->router = mock('AgileDashboardRouter');

        $this->event_manager  = \Mockery::mock(\EventManager::class);
        $this->router_builder = stub('AgileDashboardRouterBuilder')
            ->build()
            ->returns($this->router);

        $this->xml_exporter = new AgileDashboard_XMLFullStructureExporter(
            $this->event_manager,
            $this->router_builder
        );

        $this->project = stub('Project')->getID()->returns(101);
    }

    public function itAsksToPluginToExportStuffForTheGivenProject()
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                               <project />');

        $this->event_manager->shouldReceive('processEvent')->with(AGILEDASHBOARD_EXPORT_XML, \Mockery::on(function ($params) {
            return $params['project']->getID() === $this->project->getID() && isset($params['into_xml']);
        }));

        $this->xml_exporter->export($this->project);
    }

    public function testAgileDashboardExportsItself()
    {
        expect($this->router)->route()->once();

        $this->event_manager->shouldReceive('processEvent')->with(AGILEDASHBOARD_EXPORT_XML, \Mockery::any());

        $this->xml_exporter->export($this->project);
    }
}
