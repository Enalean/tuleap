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
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;

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

    private Project $project;

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
            <project />
        '
        );

        $this->xml_validator = Mockery::mock(XML_RNGValidator::class);

        $this->explicit_backlog_xml_exporter = Mockery::mock(Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter::class);
        $this->planning_xml_exporter         = Mockery::mock(Tuleap\AgileDashboard\Planning\XML\XMLExporter::class);

        $this->exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter,
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );

        $this->project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();
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

    public function testItDoesNotExportAttributeShouldSidebarDisplayLastMilestonesWhenTrueBecauseItIsTheDefault(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->shouldNotReceive('exportExplicitBacklogContent');
        $this->planning_xml_exporter->shouldReceive('exportPlannings');

        $this->xml_validator->shouldReceive('validate');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
        );
        $exporter->export($this->project, $this->xml_tree, []);

        self::assertEmpty($this->xml_tree->agiledashboard->attributes());
    }

    public function testItExportsAttributeShouldSidebarDisplayLastMilestonesWhenFalse(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->shouldNotReceive('exportExplicitBacklogContent');
        $this->planning_xml_exporter->shouldReceive('exportPlannings');

        $this->xml_validator->shouldReceive('validate');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter,
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
        $exporter->export($this->project, $this->xml_tree, []);

        self::assertSame('0', (string) $this->xml_tree->agiledashboard["should_sidebar_display_last_milestones"]);
    }

    public function testItExportsAttributeShouldSidebarDisplayLastMilestonesWhenFalseAndThereIsAlreadyExportedKanban(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->shouldNotReceive('exportExplicitBacklogContent');
        $this->planning_xml_exporter->shouldReceive('exportPlannings');

        $this->xml_validator->shouldReceive('validate');

        $xml_tree = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <agiledashboard>
                    <kanban_list>
                        <kanban />
                    </kanban_list>
                </agiledashboard>
            </project>
        '
        );
        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter,
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
        $exporter->export($this->project, $xml_tree, []);

        self::assertSame('0', (string) $xml_tree->agiledashboard["should_sidebar_display_last_milestones"]);
    }

    public function testItExportsAttributeShouldSidebarDisplayLastMilestonesWhenFalseWhenItIsAlreadyWronglyExported(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->shouldNotReceive('exportExplicitBacklogContent');
        $this->planning_xml_exporter->shouldReceive('exportPlannings');

        $this->xml_validator->shouldReceive('validate');

        $xml_tree = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <agiledashboard should_sidebar_display_last_milestones="1">
                    <kanban_list>
                        <kanban />
                    </kanban_list>
                </agiledashboard>
            </project>
        '
        );
        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter,
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
        $exporter->export($this->project, $xml_tree, []);

        self::assertSame('0', (string) $xml_tree->agiledashboard["should_sidebar_display_last_milestones"]);
    }

    public function testItRemovesWronglyExportedAttributeWhenShouldSidebarDisplayLastMilestonesIsTrueBecauseItIsTheDefault(): void
    {
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->shouldNotReceive('exportExplicitBacklogContent');
        $this->planning_xml_exporter->shouldReceive('exportPlannings');

        $this->xml_validator->shouldReceive('validate');

        $xml_tree = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <agiledashboard should_sidebar_display_last_milestones="0">
                    <kanban_list>
                        <kanban />
                    </kanban_list>
                </agiledashboard>
            </project>
        '
        );
        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_xml_exporter,
            $this->explicit_backlog_xml_exporter,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
        );
        $exporter->export($this->project, $xml_tree, []);

        self::assertEmpty($xml_tree->agiledashboard->attributes());
    }
}
