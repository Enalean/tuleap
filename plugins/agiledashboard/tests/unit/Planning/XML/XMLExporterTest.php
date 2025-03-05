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
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use PlanningParameters;
use PlanningPermissionsManager;
use SimpleXMLElement;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLExporterTest extends TestCase
{
    private XMLExporter $exporter;
    private PlanningPermissionsManager&MockObject $planning_permissions_manager;
    /**
     * @var Planning[]
     */
    private array $plannings;
    private SimpleXMLElement $agiledasboard_node;

    protected function setUp(): void
    {
        $planning = PlanningBuilder::aPlanning(101)
            ->withId(1)
            ->withName('abcd')
            ->withPlanTitle('efgh')
            ->withBacklogTitle('p q r')
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(11)->build())
            ->withBacklogTrackers(TrackerTestBuilder::aTracker()->withId(888)->build())
            ->build();

        $this->plannings = [$planning, $planning];

        $this->planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);
        $this->exporter                     = new XMLExporter($this->planning_permissions_manager);

        $this->agiledasboard_node = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard/>
        ');
    }

    public function testItCreatesAnXMLEntryForEachPlanningShortAccess(): void
    {
        $this->planning_permissions_manager->expects(self::exactly(2))->method('getGroupIdsWhoHasPermissionOnPlanning');

        $this->exporter->exportPlannings($this->agiledasboard_node, $this->plannings);

        self::assertEquals(1, count($this->agiledasboard_node->children()));

        foreach ($this->agiledasboard_node->children() as $plannings_node) {
            self::assertCount(2, $plannings_node->children());
            self::assertEquals(XMLExporter::NODE_PLANNINGS, $plannings_node->getName());
        }

        foreach ($this->agiledasboard_node->plannings->children() as $planning) {
            self::assertCount(1, $planning->children());
            self::assertEquals(XMLPlanning::NODE_PLANNING, $planning->getName());
        }
    }

    public function testItAddsAttributesForEachPlanningShortAccess(): void
    {
        $this->planning_permissions_manager->expects(self::exactly(2))->method('getGroupIdsWhoHasPermissionOnPlanning');

        $this->exporter->exportPlannings($this->agiledasboard_node, $this->plannings);

        foreach ($this->agiledasboard_node->plannings->children() as $planning) {
            $attributes = $planning->attributes();

            self::assertEquals('abcd', (string) $attributes[PlanningParameters::NAME]);
            self::assertEquals('efgh', (string) $attributes[PlanningParameters::PLANNING_TITLE]);
            self::assertEquals('p q r', (string) $attributes[PlanningParameters::BACKLOG_TITLE]);

            $expected_planning_tracker_id = XMLExporter::TRACKER_ID_PREFIX . '11';
            $expected_backlog_tracker_id  = XMLExporter::TRACKER_ID_PREFIX . 888;

            self::assertEquals(
                $expected_planning_tracker_id,
                (string) $attributes[PlanningParameters::PLANNING_TRACKER_ID]
            );
            foreach ($planning->{XMLPlanning::NODE_BACKLOGS}->children() as $backlog) {
                self::assertEquals($expected_backlog_tracker_id, (string) $backlog);
            }
        }
    }

    public function testItThrowsAnExceptionIfPlanningNameIsEmpty(): void
    {
        $planning = PlanningBuilder::aPlanning(101)
            ->withName('')
            ->withPlanTitle('')
            ->withBacklogTitle('p q r')
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(11)->build())
            ->withBacklogTrackers(TrackerTestBuilder::aTracker()->withId(888)->build())
            ->build();

        $plannings = [$planning];

        self::expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
    }

    public function testItThrowsAnExceptionIfPlanningTitleIsEmpty(): void
    {
        $planning = PlanningBuilder::aPlanning(101)
            ->withName('abc d')
            ->withPlanTitle('')
            ->withBacklogTitle('p q r')
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(11)->build())
            ->withBacklogTrackers(TrackerTestBuilder::aTracker()->withId(888)->build())
            ->build();

        $plannings = [$planning];

        self::expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
    }

    public function testItThrowsAnExceptionIfBacklogTitleIsEmpty(): void
    {
        $planning = PlanningBuilder::aPlanning(101)
            ->withName('abc d')
            ->withPlanTitle('efgh')
            ->withBacklogTitle('')
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(45)->build())
            ->withBacklogTrackers(TrackerTestBuilder::aTracker()->withId(888)->build())
            ->build();

        $plannings = [$planning];

        self::expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
    }

    public function testItThrowsAnExceptionIfPlanningTrackerIdIsEmpty(): void
    {
        $planning = PlanningBuilder::aPlanning(101)
            ->withName('abc d')
            ->withPlanTitle('efgh')
            ->withBacklogTitle('p q r')
            ->withBadConfigurationAndNoMilestoneTracker()
            ->withBacklogTrackers(TrackerTestBuilder::aTracker()->withId(888)->build())
            ->build();

        $plannings = [$planning];

        self::expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
    }

    public function testItThrowsAnExceptionIfBacklogTrackerIdIsEmpty(): void
    {
        $planning = PlanningBuilder::aPlanning(101)
            ->withName('abc d')
            ->withPlanTitle('efgh')
            ->withBacklogTitle('p q r')
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(45)->build())
            ->withBacklogTrackers(TrackerTestBuilder::aTracker()->withId(0)->build())
            ->build();

        $plannings = [$planning];

        $this->planning_permissions_manager->method('getGroupIdsWhoHasPermissionOnPlanning');
        self::expectException(AgileDashboard_XMLExporterUnableToGetValueException::class);

        $this->exporter->exportPlannings($this->agiledasboard_node, $plannings);
    }
}
