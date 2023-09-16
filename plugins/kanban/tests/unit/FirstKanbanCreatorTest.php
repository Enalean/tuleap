<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace unit;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Report;
use Tracker_ReportFactory;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Kanban\FirstKanbanCreator;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanManager;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class FirstKanbanCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&KanbanManager
     */
    private $kanban_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerXmlImport
     */
    private $xml_import;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerReportUpdater
     */
    private $report_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ReportFactory
     */
    private $report_factory;
    private FirstKanbanCreator $kanban_creator;

    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        $this->tracker_factory = $this->createMock(TrackerFactory::class);
        $this->kanban_manager  = $this->createMock(KanbanManager::class);
        $this->kanban_factory  = $this->createMock(KanbanFactory::class);
        $this->xml_import      = $this->createMock(TrackerXmlImport::class);
        $this->xml_import->method('getTrackerItemNameFromXMLFile')->willReturn('tracker_item_name');
        $this->report_updater = $this->createMock(TrackerReportUpdater::class);
        $this->report_factory = $this->createMock(Tracker_ReportFactory::class);
        $this->kanban_creator = new FirstKanbanCreator(
            $project,
            $this->kanban_manager,
            $this->tracker_factory,
            $this->xml_import,
            $this->kanban_factory,
            $this->report_updater,
            $this->report_factory
        );
    }

    public function testCreationFirstKanban(): void
    {
        $this->kanban_manager->method('getTrackersUsedAsKanban')->willReturn([]);
        $this->tracker_factory->method('isShortNameExists')->willReturn(false);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getName')->willReturn('tracker_name');
        $this->xml_import->method('createFromXMLFile')->willReturn($tracker);
        $kanban = $this->createMock(Kanban::class);
        $this->kanban_factory->method('getKanban')->willReturn($kanban);

        $this->kanban_manager->expects(self::once())->method('createKanban');

        $this->kanban_creator->createFirstKanban($this->createMock(PFUser::class));
    }

    public function testItAddsAssignedToMeReportAsSelectableReport(): void
    {
        $default_report        = $this->buildReport(10, true, 'Default');
        $assigned_to_me_report = $this->buildReport(20, true, 'Assigned to me');

        $this->kanban_manager->method('getTrackersUsedAsKanban')->willReturn([]);
        $this->tracker_factory->method('isShortNameExists')->willReturn(false);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getName')->willReturn('tracker_name');
        $this->xml_import->method('createFromXMLFile')->willReturn($tracker);
        $kanban = $this->createMock(Kanban::class);
        $this->kanban_factory->method('getKanban')->willReturn($kanban);
        $this->kanban_manager->method('createKanban')->willReturn(1);
        $this->report_factory->method('getReportsByTrackerId')->with(101, null)->willReturn(
            [$default_report, $assigned_to_me_report]
        );

        $this->report_updater->expects(self::once())->method('save')->with($kanban, [20]);

        $this->kanban_creator->createFirstKanban($this->createMock(PFUser::class));
    }

    public function testItDoesNotAddAReportAsSelectableReportIfAssignedToMeReportNotFound(): void
    {
        $default_report = $this->buildReport(10, true, 'Default');

        $this->kanban_manager->method('getTrackersUsedAsKanban')->willReturn([]);
        $this->tracker_factory->method('isShortNameExists')->willReturn(false);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getName')->willReturn('tracker_name');
        $this->xml_import->method('createFromXMLFile')->willReturn($tracker);
        $kanban = $this->createMock(Kanban::class);
        $this->kanban_factory->method('getKanban')->willReturn($kanban);
        $this->kanban_manager->method('createKanban')->willReturn(1);
        $this->report_factory->method('getReportsByTrackerId')->with(101, null)->willReturn(
            [$default_report]
        );

        $this->report_updater->expects(self::never())->method('save');

        $this->kanban_creator->createFirstKanban($this->createMock(PFUser::class));
    }

    public function testItDoesNotAddAReportAsSelectableReportIfNoPublicReportFound(): void
    {
        $default_report = $this->buildReport(10, true, 'Default');

        $this->kanban_manager->method('getTrackersUsedAsKanban')->willReturn([]);
        $this->tracker_factory->method('isShortNameExists')->willReturn(false);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getName')->willReturn('tracker_name');
        $this->xml_import->method('createFromXMLFile')->willReturn($tracker);
        $kanban = $this->createMock(Kanban::class);
        $this->kanban_factory->method('getKanban')->willReturn($kanban);
        $this->kanban_manager->method('createKanban')->willReturn(1);
        $this->report_factory->method('getReportsByTrackerId')->with(101, null)->willReturn(
            [$default_report]
        );

        $this->report_updater->expects(self::never())->method('save');

        $this->kanban_creator->createFirstKanban($this->createMock(PFUser::class));
    }

    public function testItDoesNotAddTwiceAssignedToMeReportAsSelectableReport(): void
    {
        $default_report              = $this->buildReport(10, true, 'Default');
        $assigned_to_me_report       = $this->buildReport(15, true, 'Assigned to me');
        $other_assigned_to_me_report = $this->buildReport(20, true, 'Other assigned to me');

        $this->kanban_manager->method('getTrackersUsedAsKanban')->willReturn([]);
        $this->tracker_factory->method('isShortNameExists')->willReturn(false);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getName')->willReturn('tracker_name');
        $this->xml_import->method('createFromXMLFile')->willReturn($tracker);
        $kanban = $this->createMock(Kanban::class);
        $this->kanban_factory->method('getKanban')->willReturn($kanban);
        $this->kanban_manager->method('createKanban')->willReturn(1);
        $this->report_factory->method('getReportsByTrackerId')->with(101, null)->willReturn(
            [$default_report, $assigned_to_me_report, $other_assigned_to_me_report]
        );

        $this->report_updater->expects(self::once())->method('save')->with($kanban, self::anything());

        $this->kanban_creator->createFirstKanban($this->createMock(PFUser::class));
    }

    private function buildReport(int $id, bool $is_public, string $name): MockObject&Tracker_Report
    {
        $report = $this->createMock(Tracker_Report::class);
        $report->method('getId')->willReturn($id);
        $report->method('isPublic')->willReturn($is_public);
        $report->method('getName')->willReturn($name);

        return $report;
    }
}
