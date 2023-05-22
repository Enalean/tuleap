<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Report;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Report_Renderer;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_ReportFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\Mock
     */
    private $report_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Report_RendererFactory
     */
    private $renderer_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report_factory = \Mockery::mock(\Tracker_ReportFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $criteria_factory     = \Mockery::spy(\Tracker_Report_CriteriaFactory::class);
        $this->report_factory->shouldReceive('getCriteriaFactory')->andReturns($criteria_factory);
        $this->renderer_factory = \Mockery::spy(\Tracker_Report_RendererFactory::class);
        $this->report_factory->shouldReceive('getRendererFactory')->andReturns($this->renderer_factory);
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }

        parent::tearDown();
    }

    public function testImport(): void
    {
        $xml                   = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TestTracker-1.xml'));
        $reports               = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];
        foreach ($xml->reports->report as $report) {
            $empty_array = [];
            $reports[]   = $this->report_factory->getInstanceFromXML($report, $empty_array, $reports_xml_mapping, $renderers_xml_mapping, 0);
        }

        //general settings
        $this->assertEquals('Default', $reports[0]->name);
        $this->assertEquals('The system default artifact report', $reports[0]->description);
        $this->assertEquals(0, $reports[0]->is_default);

        $this->assertEquals($reports[0], $reports_xml_mapping['REPORT_979']);

        //default values
        $this->assertEquals(1, $reports[0]->is_query_displayed);
        $this->assertEquals(0, $reports[0]->is_in_expert_mode);
        $this->assertEmpty($renderers_xml_mapping);
    }

    public function testImportWithRendererID(): void
    {
        $renderer = Mockery::mock(Tracker_Report_Renderer::class);
        $this->renderer_factory->shouldReceive('getInstanceFromXML')->andReturn($renderer);

        $xml                   = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/tracker_with_renderer_id.xml'));
        $reports               = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];
        foreach ($xml->reports->report as $report) {
            $empty_array = [];
            $reports[]   = $this->report_factory->getInstanceFromXML($report, $empty_array, $reports_xml_mapping, $renderers_xml_mapping, 0);
        }

        //general settings
        $this->assertEquals('Default', $reports[0]->name);
        $this->assertEquals('The system default artifact report', $reports[0]->description);
        $this->assertEquals(0, $reports[0]->is_default);

        //default values
        $this->assertEquals(1, $reports[0]->is_query_displayed);
        $this->assertEquals(0, $reports[0]->is_in_expert_mode);

        $expected_mapping = [
            "R1" => $renderer,
        ];

        $this->assertEquals($expected_mapping, $renderers_xml_mapping);
    }
}
