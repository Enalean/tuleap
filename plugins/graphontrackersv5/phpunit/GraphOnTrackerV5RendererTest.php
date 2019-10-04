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

namespace Tuleap\GraphOnTrackersV5;

use GraphOnTrackersV5_Chart_Bar;
use GraphOnTrackersV5_Chart_CumulativeFlow;
use GraphOnTrackersV5_Chart_Gantt;
use GraphOnTrackersV5_Chart_Pie;
use GraphOnTrackersV5_ChartFactory;
use GraphOnTrackersV5_Renderer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tracker_Report;
use UserManager;

class GraphOnTrackerV5RendererTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GraphOnTrackersV5_Renderer
     */
    private $graph_renderer;

    /**
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Mockery\MockInterface|GraphOnTrackersV5_Chart_CumulativeFlow
     */
    private $chart;

    /**
     * @var array
     */
    private $form_mapping;

    /**
     * @var SimpleXMLElement
     */
    private $xml_element;

    /**
     * @var GraphOnTrackersV5_ChartFactory|Mockery\MockInterface
     */
    private $chart_factory;

    public function setUp(): void
    {
        $report                     = Mockery::mock(Tracker_Report::class);
        $user_manager               = Mockery::mock(UserManager::class);
        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->form_mapping         = ['F3767' => '3767'];

        $this->chart = Mockery::mock(GraphOnTrackersV5_Chart_CumulativeFlow::class);
        $this->chart->shouldReceive('getFieldId')->andReturn(3767);

        $this->chart_factory = Mockery::mock(GraphOnTrackersV5_ChartFactory::class);

        $this->xml_element = new SimpleXMLElement('<renderer></renderer>');


        $this->graph_renderer = Mockery::mock(
            GraphOnTrackersV5_Renderer::class,
            [18, $report, '', '', 0, '', $user_manager, $this->form_element_factory]
        )->makePartial()
         ->shouldAllowMockingProtectedMethods();

        $this->graph_renderer->shouldReceive('getChartFactory')->andReturn($this->chart_factory);
    }

    public function testExportIfFieldUsedOnCumulativeChart(): void
    {
        $this->chart->shouldReceive('exportToXML');
        $this->chart_factory->shouldReceive('getCharts')->andReturn([$this->chart]);
        $this->form_element_factory->shouldReceive('getUsedFormElementById')->andReturn(true);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testNoExportIfFieldNotUsedOnCumulativeChart(): void
    {
        $this->chart->shouldNotReceive('exportToXML');
        $this->chart_factory->shouldReceive('getCharts')->andReturn([$this->chart]);
        $this->form_element_factory->shouldReceive('getUsedFormElementById')->andReturn(false);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testExportIfFieldNotUsedOnGantt(): void
    {
        $chart = Mockery::mock(GraphOnTrackersV5_Chart_Gantt::class);
        $chart->shouldReceive('exportToXML');
        $this->chart_factory->shouldReceive('getCharts')->andReturn([$chart]);
        $this->form_element_factory->shouldNotReceive('getUsedFormElementById');

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testExportIfFieldNotUsedOnPie(): void
    {
        $chart = Mockery::mock(GraphOnTrackersV5_Chart_Pie::class);
        $chart->shouldReceive('exportToXML');
        $this->form_element_factory->shouldNotReceive('getUsedFormElementById');
        $this->chart_factory->shouldReceive('getCharts')->andReturn([$chart]);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testExportIfFieldNotUsedOnBar(): void
    {
        $chart = Mockery::mock(GraphOnTrackersV5_Chart_Bar::class);
        $chart->shouldReceive('exportToXML');
        $this->form_element_factory->shouldNotReceive('getUsedFormElementById');
        $this->chart_factory->shouldReceive('getCharts')->andReturn([$chart]);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }
}
