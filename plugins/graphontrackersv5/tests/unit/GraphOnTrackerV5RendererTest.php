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

namespace Tuleap\GraphOnTrackersV5;

use GraphOnTrackersV5_Chart_Bar;
use GraphOnTrackersV5_Chart_CumulativeFlow;
use GraphOnTrackersV5_Chart_Gantt;
use GraphOnTrackersV5_Chart_Pie;
use GraphOnTrackersV5_ChartFactory;
use GraphOnTrackersV5_Renderer;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tracker_Report;
use UserManager;

final class GraphOnTrackerV5RendererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GraphOnTrackersV5_Renderer&\PHPUnit\Framework\MockObject\MockObject $graph_renderer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GraphOnTrackersV5_Chart_CumulativeFlow
     */
    private $chart;

    private array $form_mapping;
    private SimpleXMLElement $xml_element;

    /**
     * @var GraphOnTrackersV5_ChartFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $chart_factory;

    public function setUp(): void
    {
        $report                     = $this->createMock(Tracker_Report::class);
        $user_manager               = $this->createMock(UserManager::class);
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->form_mapping         = ['F3767' => '3767'];

        $this->chart = $this->createMock(GraphOnTrackersV5_Chart_CumulativeFlow::class);
        $this->chart->method('getFieldId')->willReturn(3767);

        $this->chart_factory = $this->createMock(GraphOnTrackersV5_ChartFactory::class);

        $this->xml_element = new SimpleXMLElement('<renderer></renderer>');

        $this->graph_renderer = $this->getMockBuilder(GraphOnTrackersV5_Renderer::class)
            ->onlyMethods(['getChartFactory'])
            ->setConstructorArgs([18, $report, '', '', 0, '', $user_manager, $this->form_element_factory])
            ->getMock();

        $this->graph_renderer->method('getChartFactory')->willReturn($this->chart_factory);
    }

    public function testExportIfFieldUsedOnCumulativeChart(): void
    {
        $this->chart->expects(self::atLeast(1))->method('exportToXML');
        $this->chart_factory->method('getCharts')->willReturn([$this->chart]);
        $this->form_element_factory->method('getUsedFormElementById')->willReturn(true);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testNoExportIfFieldNotUsedOnCumulativeChart(): void
    {
        $this->chart->expects(self::never())->method('exportToXML');
        $this->chart_factory->method('getCharts')->willReturn([$this->chart]);
        $this->form_element_factory->method('getUsedFormElementById')->willReturn(false);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testExportIfFieldNotUsedOnGantt(): void
    {
        $chart = $this->createMock(GraphOnTrackersV5_Chart_Gantt::class);
        $chart->method('exportToXML');
        $this->chart_factory->method('getCharts')->willReturn([$chart]);
        $this->form_element_factory->expects(self::never())->method('getUsedFormElementById');

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testExportIfFieldNotUsedOnPie(): void
    {
        $chart = $this->createMock(GraphOnTrackersV5_Chart_Pie::class);
        $chart->method('exportToXML');
        $this->form_element_factory->expects(self::never())->method('getUsedFormElementById');
        $this->chart_factory->method('getCharts')->willReturn([$chart]);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }

    public function testExportIfFieldNotUsedOnBar(): void
    {
        $chart = $this->createMock(GraphOnTrackersV5_Chart_Bar::class);
        $chart->method('exportToXML');
        $this->form_element_factory->expects(self::never())->method('getUsedFormElementById');
        $this->chart_factory->method('getCharts')->willReturn([$chart]);

        $this->graph_renderer->exportToXml($this->xml_element, $this->form_mapping);
    }
}
