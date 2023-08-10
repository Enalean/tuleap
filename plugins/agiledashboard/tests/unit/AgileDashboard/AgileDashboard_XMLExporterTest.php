<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_XMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XML_RNGValidator
     */
    private $xml_validator;

    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter
     */
    private $explicit_backlog_xml_exporter;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\AgileDashboard\Planning\XML\XMLExporter
     */
    private $planning_xml_exporter;

    /**
     * @var AgileDashboard_XMLExporter
     */
    private $exporter;

    protected function setUp(): void
    {
        $this->xml_tree = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <plannings />
        '
        );

        $this->xml_validator = Mockery::mock(XML_RNGValidator::class);

        $this->explicit_backlog_xml_exporter = Mockery::mock(Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter::class);
        $this->planning_xml_exporter         = Mockery::mock(Tuleap\AgileDashboard\Planning\XML\XMLExporter::class);

        $this->exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter
        );

        $this->project = Mockery::mock(Project::class);
    }

    public function testItUpdatesASimpleXMlElement(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration')->once();
        $this->explicit_backlog_xml_exporter->shouldNotReceive('exportExplicitBacklogContent');
        $this->planning_xml_exporter->shouldReceive('exportPlannings')->once();

        $this->xml_validator->shouldReceive('validate')->once();

        $this->exporter->export($this->project, $this->xml_tree, []);
    }

    public function testItUpdatesASimpleXMlElementWithExplicitBacklogContentInFullExport(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration')->once();
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogContent')->once();
        $this->planning_xml_exporter->shouldReceive('exportPlannings')->once();

        $this->xml_validator->shouldReceive('validate')->once();

        $this->exporter->exportFull($this->project, $this->xml_tree, []);
    }

    public function testItThrowsAnExceptionIfXmlGeneratedIsNotValid(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration')->once();
        $this->planning_xml_exporter->shouldReceive('exportPlannings')->once();

        $this->xml_validator->shouldReceive('validate')->once()->andThrows(new \Tuleap\XML\ParseExceptionWithErrors('', [], []));

        $this->expectException(XML_ParseException::class);

        $this->exporter->export($this->project, $this->xml_tree, []);
    }
}
