<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning\XML;

use AgileDashboard_XMLExporterUnableToGetValueException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningParameters;
use PlanningPermissionsManager;
use SimpleXMLElement;
use Tracker;

class XMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLExporter
     */
    private $exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $planning1;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $planning2;

    /**
     * @var Planning[]
     */
    private $plannings;

    /**
     * @var SimpleXMLElement
     */
    private $agiledasboard_node;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->planning_permissions_manager = Mockery::mock(PlanningPermissionsManager::class);
        $this->exporter = new XMLExporter($this->planning_permissions_manager);

        $this->agiledasboard_node = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard/>
        ');
    }

    public function testItCreatesAnXMLEntryForEachPlanningShortAccess(): void
    {
        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')->twice();

        $this->exporter->exportPlannings($this->agiledasboard_node, $this->plannings);

        $this->assertEquals(1, count($this->agiledasboard_node->children()));

        foreach ($this->agiledasboard_node->children() as $plannings_node) {
            $this->assertCount(2, $plannings_node->children());
            $this->assertEquals(XMLExporter::NODE_PLANNINGS, $plannings_node->getName());
        }

        foreach ($this->agiledasboard_node->plannings->children() as $planning) {
            $this->assertCount(1, $planning->children());
            $this->assertEquals(XMLExporter::NODE_PLANNING, $planning->getName());
        }
    }

    public function testItAddsAttributesForEachPlanningShortAccess(): void
    {
        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')->twice();

        $this->exporter->exportPlannings($this->agiledasboard_node, $this->plannings);

        foreach ($this->agiledasboard_node->plannings->children() as $planning) {
            $attributes = $planning->attributes();

            $this->assertEquals('abcd', (string) $attributes[PlanningParameters::NAME]);
            $this->assertEquals('efgh', (string) $attributes[PlanningParameters::PLANNING_TITLE]);
            $this->assertEquals('p q r', (string) $attributes[PlanningParameters::BACKLOG_TITLE]);

            $expected_planning_tracker_id = XMLExporter::TRACKER_ID_PREFIX . 'ijklmon';
            $expected_backlog_tracker_id  = XMLExporter::TRACKER_ID_PREFIX . 888;

            $this->assertEquals(
                $expected_planning_tracker_id,
                (string) $attributes[PlanningParameters::PLANNING_TRACKER_ID]
            );
            foreach ($planning->{XMLExporter::NODE_BACKLOGS}->children() as $backlog) {
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

        $this->expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
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

        $this->expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
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

        $this->expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
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

        $this->expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
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

        $this->expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
    }
}
