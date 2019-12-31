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
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_XMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningPermissionsManager
     */
    private $planning_permissions_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XML_RNGValidator
     */
    private $xml_validator;
    /**
     * @var \Mockery\LegacyMockInterface[]|\Mockery\MockInterface[]|Planning []
     */
    private $plannings;
    /**
     *
     * @var SimpleXMLElement
     */
    private $xml_tree;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning
     */
    private $planning1;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning
     */
    private $planning2;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XMLExporter
     */
    private $explicit_backlog_xml_exporter;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->planning1 = Mockery::mock(Planning::class);
        $this->planning2 = Mockery::mock(Planning::class);

        $this->plannings = [
            $this->planning1,
            $this->planning2,
        ];

        $this->planning1->shouldReceive('getId')->andReturn('1');
        $this->planning2->shouldReceive('getId')->andReturn('2');

        $this->planning1->shouldReceive('getGroupId')->andReturn('101');
        $this->planning2->shouldReceive('getGroupId')->andReturn('101');

        $this->planning1->shouldReceive('getName')->andReturn('abcd');
        $this->planning2->shouldReceive('getName')->andReturn('abcd');

        $this->planning1->shouldReceive('getPlanTitle')->andReturn('efgh');
        $this->planning2->shouldReceive('getPlanTitle')->andReturn('efgh');

        $this->planning1->shouldReceive('getPlanningTrackerId')->andReturn('ijklmon');
        $this->planning2->shouldReceive('getPlanningTrackerId')->andReturn('ijklmon');

        $this->planning1->shouldReceive('getBacklogTitle')->andReturn('p q r');
        $this->planning2->shouldReceive('getBacklogTitle')->andReturn('p q r');

        $backlog_tracker1 = Mockery::spy(Tracker::class);
        $backlog_tracker2 = Mockery::spy(Tracker::class);

        $backlog_tracker1->shouldReceive('getId')->andReturn(888);
        $backlog_tracker2->shouldReceive('getId')->andReturn(888);

        $this->planning1->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker1]);
        $this->planning2->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker2]);

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <plannings />';

        $this->xml_tree = new SimpleXMLElement($data);

        $this->xml_validator = Mockery::mock(XML_RNGValidator::class);
        $this->planning_permissions_manager = Mockery::mock(PlanningPermissionsManager::class);

        $this->explicit_backlog_xml_exporter = Mockery::mock(XMLExporter::class);
        $this->explicit_backlog_xml_exporter->shouldReceive('exportExplicitBacklogConfiguration')->once();

        $this->project = Mockery::mock(Project::class);
    }

    public function testItUpdatesASimpleXMlElement(): void
    {
        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );

        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')->twice();
        $this->xml_validator->shouldReceive('validate')->once();

        $xml = $this->xml_tree;
        $exporter->export($this->project, $this->xml_tree, $this->plannings);

        $this->assertEquals($xml, $this->xml_tree);
    }

    public function testItCreatesAnXMLEntryForEachPlanningShortAccess(): void
    {
        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')->twice();
        $this->xml_validator->shouldReceive('validate')->once();

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $this->plannings);

        $this->assertEquals(1, count($this->xml_tree->children()));

        $agiledashborad = AgileDashboard_XMLExporter::NODE_AGILEDASHBOARD;
        $plannings      = AgileDashboard_XMLExporter::NODE_PLANNINGS;

        foreach ($this->xml_tree->$agiledashborad->children() as $plannings_node) {
            $this->assertEquals(2, count($plannings_node->children()));
            $this->assertEquals($plannings, $plannings_node->getName());
        }

        foreach ($this->xml_tree->$agiledashborad->$plannings->children() as $planning) {
            $this->assertEquals(AgileDashboard_XMLExporter::NODE_PLANNING, $planning->getName());
            $this->assertEquals(1, count($planning->children()));
        }
    }

    public function testItAddsAttributesForEachPlanningShortAccess(): void
    {
        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')->twice();
        $this->xml_validator->shouldReceive('validate')->once();

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $this->plannings);

        $agiledashborad = AgileDashboard_XMLExporter::NODE_AGILEDASHBOARD;
        $plannings      = AgileDashboard_XMLExporter::NODE_PLANNINGS;

        foreach ($this->xml_tree->$agiledashborad->$plannings->children() as $planning) {
            $attributes = $planning->attributes();

            $this->assertEquals((string) $attributes[PlanningParameters::NAME], 'abcd');
            $this->assertEquals((string) $attributes[PlanningParameters::PLANNING_TITLE], 'efgh');
            $this->assertEquals((string) $attributes[PlanningParameters::BACKLOG_TITLE], 'p q r');

            $expected_planning_tracker_id = AgileDashboard_XMLExporter::TRACKER_ID_PREFIX . 'ijklmon';
            $expected_backlog_tracker_id  = AgileDashboard_XMLExporter::TRACKER_ID_PREFIX . 888;

            $this->assertEquals(
                $expected_planning_tracker_id,
                (string) $attributes[PlanningParameters::PLANNING_TRACKER_ID]
            );
            foreach ($planning->{AgileDashboard_XMLExporter::NODE_BACKLOGS}->children() as $backlog) {
                $this->assertEquals($expected_backlog_tracker_id, (string) $backlog);
            }
        }
    }

    public function testItThrowsAnExceptionIfPlanningNameIsEmpty(): void
    {
        $planning = Mockery::mock(Planning::class);

        $plannings = [
            $planning,
        ];

        $planning->shouldReceive('getName')->andReturn(null);
        $planning->shouldReceive('getPlanTitle')->andReturn('efgh');
        $planning->shouldReceive('getPlanningTrackerId')->andReturn('ijklmon');
        $planning->shouldReceive('getBacklogTitle')->andReturn('p q r');

        $backlog_tracker = Mockery::spy(Tracker::class);
        $backlog_tracker->shouldReceive('getId')->andReturn(888);
        $planning->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker]);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $plannings);
    }

    public function testItThrowsAnExceptionIfPlanningTitleIsEmpty(): void
    {
        $planning = Mockery::mock(Planning::class);

        $plannings = [
            $planning,
        ];

        $planning->shouldReceive('getName')->andReturn('abc d');
        $planning->shouldReceive('getPlanTitle')->andReturn('');
        $planning->shouldReceive('getPlanningTrackerId')->andReturn('ijklmon');
        $planning->shouldReceive('getBacklogTitle')->andReturn('p q r');

        $backlog_tracker = Mockery::spy(Tracker::class);
        $backlog_tracker->shouldReceive('getId')->andReturn(888);
        $planning->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker]);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $plannings);
    }

    public function testItThrowsAnExceptionIfBacklogTitleIsEmpty(): void
    {
        $planning = Mockery::mock(Planning::class);

        $plannings = [
            $planning,
        ];

        $planning->shouldReceive('getName')->andReturn('abc d');
        $planning->shouldReceive('getPlanTitle')->andReturn('efgh');
        $planning->shouldReceive('getPlanningTrackerId')->andReturn(45);
        $planning->shouldReceive('getBacklogTitle')->andReturn(null);

        $backlog_tracker = Mockery::spy(Tracker::class);
        $backlog_tracker->shouldReceive('getId')->andReturn(888);
        $planning->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker]);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $plannings);
    }

    public function testItThrowsAnExceptionIfPlanningTrackerIdIsEmpty(): void
    {
        $planning = Mockery::mock(Planning::class);

        $plannings = [
            $planning,
        ];

        $planning->shouldReceive('getName')->andReturn('abc d');
        $planning->shouldReceive('getPlanTitle')->andReturn('efgh');
        $planning->shouldReceive('getPlanningTrackerId')->andReturn(null);
        $planning->shouldReceive('getBacklogTitle')->andReturn('p q r');

        $backlog_tracker = Mockery::spy(Tracker::class);
        $backlog_tracker->shouldReceive('getId')->andReturn(888);
        $planning->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker]);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $plannings);
    }

    public function testItThrowsAnExceptionIfBacklogTrackerIdIsEmpty(): void
    {
        $planning = Mockery::mock(Planning::class);

        $plannings = [
            $planning,
        ];

        $planning->shouldReceive('getName')->andReturn('abc d');
        $planning->shouldReceive('getPlanTitle')->andReturn('efgh');
        $planning->shouldReceive('getPlanningTrackerId')->andReturn(78);
        $planning->shouldReceive('getBacklogTitle')->andReturn('p q r');

        $backlog_tracker = Mockery::spy(Tracker::class);
        $backlog_tracker->shouldReceive('getId')->andReturn(0);
        $planning->shouldReceive('getBacklogTrackers')->andReturn([$backlog_tracker]);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );
        $exporter->export($this->project, $this->xml_tree, $plannings);
    }

    public function testItThrowsAnExceptionIfXmlGeneratedIsNotValid(): void
    {
        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')->twice();
        $this->xml_validator->shouldReceive('validate')->once()->andThrows(new XML_ParseException('', [], []));

        $exporter = new AgileDashboard_XMLExporter(
            $this->xml_validator,
            $this->planning_permissions_manager,
            $this->explicit_backlog_xml_exporter
        );

        $this->expectException(XML_ParseException::class);
        $exporter->export($this->project, $this->xml_tree, $this->plannings);
    }
}
