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

use Mockery;
use PFUser;
use Project;
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

final class FirstKanbanCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\MockInterface|KanbanManager
     */
    private $kanban_manager;
    /**
     * @var Mockery\MockInterface|KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var Mockery\MockInterface|TrackerXmlImport
     */
    private $xml_import;
    /**
     * @var Mockery\MockInterface|TrackerReportUpdater
     */
    private $report_updater;
    /**
     * @var Mockery\MockInterface|Tracker_ReportFactory
     */
    private $report_factory;
    /**
     * @var Mockery\MockInterface|FirstKanbanCreator
     */
    private $kanban_creator;

    protected function setUp(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(123);

        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        $this->kanban_manager  = Mockery::mock(KanbanManager::class);
        $this->kanban_factory  = Mockery::mock(KanbanFactory::class);
        $this->xml_import      = Mockery::mock(TrackerXmlImport::class);
        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')->andReturn('tracker_item_name');
        $this->report_updater = Mockery::mock(TrackerReportUpdater::class);
        $this->report_factory = Mockery::mock(Tracker_ReportFactory::class);
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
        $this->kanban_manager->shouldReceive('getTrackersUsedAsKanban')->andReturn([]);
        $this->tracker_factory->shouldReceive('isShortNameExists')->andReturn(false);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $this->xml_import->shouldReceive('createFromXMLFile')->andReturn($tracker);
        $kanban = Mockery::mock(Kanban::class);
        $this->kanban_factory->shouldReceive('getKanban')->andReturn($kanban);

        $this->kanban_manager->shouldReceive('createKanban')->once();

        $this->kanban_creator->createFirstKanban(Mockery::mock(PFUser::class));
    }

    public function testItAddsAssignedToMeReportAsSelectableReport(): void
    {
        $default_report        = $this->buildReport(10, true, 'Default');
        $assigned_to_me_report = $this->buildReport(20, true, 'Assigned to me');

        $this->kanban_manager->shouldReceive('getTrackersUsedAsKanban')->andReturn([]);
        $this->tracker_factory->shouldReceive('isShortNameExists')->andReturn(false);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $this->xml_import->shouldReceive('createFromXMLFile')->andReturn($tracker);
        $kanban = Mockery::mock(Kanban::class);
        $this->kanban_factory->shouldReceive('getKanban')->andReturn($kanban);
        $this->kanban_manager->shouldReceive('createKanban')->andReturn(1);
        $this->report_factory->shouldReceive('getReportsByTrackerId')->with(101, null)->andReturn(
            [$default_report, $assigned_to_me_report]
        );

        $this->report_updater->shouldReceive('save')->with($kanban, [20])->once();

        $this->kanban_creator->createFirstKanban(Mockery::mock(PFUser::class));
    }

    public function testItDoesNotAddAReportAsSelectableReportIfAssignedToMeReportNotFound(): void
    {
        $default_report = $this->buildReport(10, true, 'Default');

        $this->kanban_manager->shouldReceive('getTrackersUsedAsKanban')->andReturn([]);
        $this->tracker_factory->shouldReceive('isShortNameExists')->andReturn(false);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $this->xml_import->shouldReceive('createFromXMLFile')->andReturn($tracker);
        $kanban = Mockery::mock(Kanban::class);
        $this->kanban_factory->shouldReceive('getKanban')->andReturn($kanban);
        $this->kanban_manager->shouldReceive('createKanban')->andReturn(1);
        $this->report_factory->shouldReceive('getReportsByTrackerId')->with(101, null)->andReturn(
            [$default_report]
        );

        $this->report_updater->shouldNotReceive('save');

        $this->kanban_creator->createFirstKanban(Mockery::mock(PFUser::class));
    }

    public function testItDoesNotAddAReportAsSelectableReportIfNoPublicReportFound(): void
    {
        $default_report = $this->buildReport(10, true, 'Default');

        $this->kanban_manager->shouldReceive('getTrackersUsedAsKanban')->andReturn([]);
        $this->tracker_factory->shouldReceive('isShortNameExists')->andReturn(false);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $this->xml_import->shouldReceive('createFromXMLFile')->andReturn($tracker);
        $kanban = Mockery::mock(Kanban::class);
        $this->kanban_factory->shouldReceive('getKanban')->andReturn($kanban);
        $this->kanban_manager->shouldReceive('createKanban')->andReturn(1);
        $this->report_factory->shouldReceive('getReportsByTrackerId')->with(101, null)->andReturn(
            [$default_report]
        );

        $this->report_updater->shouldNotReceive('save');

        $this->kanban_creator->createFirstKanban(Mockery::mock(PFUser::class));
    }

    public function testItDoesNotAddTwiceAssignedToMeReportAsSelectableReport(): void
    {
        $default_report              = $this->buildReport(10, true, 'Default');
        $assigned_to_me_report       = $this->buildReport(15, true, 'Assigned to me');
        $other_assigned_to_me_report = $this->buildReport(20, true, 'Other assigned to me');

        $this->kanban_manager->shouldReceive('getTrackersUsedAsKanban')->andReturn([]);
        $this->tracker_factory->shouldReceive('isShortNameExists')->andReturn(false);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $this->xml_import->shouldReceive('createFromXMLFile')->andReturn($tracker);
        $kanban = Mockery::mock(Kanban::class);
        $this->kanban_factory->shouldReceive('getKanban')->andReturn($kanban);
        $this->kanban_manager->shouldReceive('createKanban')->andReturn(1);
        $this->report_factory->shouldReceive('getReportsByTrackerId')->with(101, null)->andReturn(
            [$default_report, $assigned_to_me_report, $other_assigned_to_me_report]
        );

        $this->report_updater->shouldReceive('save')->with($kanban, Mockery::any())->once();

        $this->kanban_creator->createFirstKanban(Mockery::mock(PFUser::class));
    }

    private function buildReport(int $id, bool $is_public, string $name): Tracker_Report
    {
        $report = Mockery::mock(Tracker_Report::class);
        $report->shouldReceive('getId')->andReturn($id);
        $report->shouldReceive('isPublic')->andReturn($is_public);
        $report->shouldReceive('getName')->andReturn($name);

        return $report;
    }
}
