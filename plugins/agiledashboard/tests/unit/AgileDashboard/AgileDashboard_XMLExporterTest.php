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

use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_XMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    private Project $project;

    /**
     * @var AgileDashboard_XMLExporter
     */
    private $exporter;
    private \PHPUnit\Framework\MockObject\MockObject|XML_RNGValidator $xml_validator;
    private \Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter|\PHPUnit\Framework\MockObject\MockObject $explicit_backlog_xml_exporter;
    private \Tuleap\AgileDashboard\Planning\XML\XMLExporter|\PHPUnit\Framework\MockObject\MockObject $planning_xml_exporter;

    protected function setUp(): void
    {
        $this->xml_tree = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project />
        '
        );

        $this->xml_validator = $this->createMock(XML_RNGValidator::class);

        $this->explicit_backlog_xml_exporter = $this->createMock(Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter::class);
        $this->planning_xml_exporter         = $this->createMock(Tuleap\AgileDashboard\Planning\XML\XMLExporter::class);

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
        $this->explicit_backlog_xml_exporter->expects(self::once())->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::never())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->expects(self::once())->method('exportPlannings');

        $this->xml_validator->expects(self::once())->method('validate');

        $this->exporter->export($this->project, $this->xml_tree, []);
    }

    public function testItUpdatesASimpleXMlElementWithExplicitBacklogContentInFullExport(): void
    {
        $this->explicit_backlog_xml_exporter->expects(self::once())->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::once())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->expects(self::once())->method('exportPlannings');

        $this->xml_validator->expects(self::once())->method('validate');

        $this->exporter->exportFull($this->project, $this->xml_tree, []);
    }

    public function testItThrowsAnExceptionIfXmlGeneratedIsNotValid(): void
    {
        $this->explicit_backlog_xml_exporter->expects(self::once())->method('exportExplicitBacklogConfiguration');
        $this->planning_xml_exporter->expects(self::once())->method('exportPlannings');

        $this->xml_validator->expects(self::once())->method('validate')->willThrowException(new \Tuleap\XML\ParseExceptionWithErrors('', [], []));

        $this->expectException(XML_ParseException::class);

        $this->exporter->export($this->project, $this->xml_tree, []);
    }

    public function testItDoesNotExportAttributeShouldSidebarDisplayLastMilestonesWhenTrueBecauseItIsTheDefault(): void
    {
        $this->explicit_backlog_xml_exporter->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::never())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->method('exportPlannings');

        $this->xml_validator->method('validate');

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
        $this->explicit_backlog_xml_exporter->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::never())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->method('exportPlannings');

        $this->xml_validator->method('validate');

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
        $this->explicit_backlog_xml_exporter->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::never())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->method('exportPlannings');

        $this->xml_validator->method('validate');

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
        $this->explicit_backlog_xml_exporter->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::never())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->method('exportPlannings');

        $this->xml_validator->method('validate');

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
        $this->explicit_backlog_xml_exporter->method('exportExplicitBacklogConfiguration');
        $this->explicit_backlog_xml_exporter->expects(self::never())->method('exportExplicitBacklogContent');
        $this->planning_xml_exporter->method('exportPlannings');

        $this->xml_validator->method('validate');

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
