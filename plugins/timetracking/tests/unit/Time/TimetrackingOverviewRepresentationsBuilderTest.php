<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registered trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Timetracking\Time;

use Project;
use Tracker_REST_TrackerRestBuilder;
use TrackerFactory;
use Tuleap\Color\ItemColor;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\REST\v1\TimetrackingOverviewRepresentationsBuilder;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimetrackingOverviewRepresentationsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AdminDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $admin_dao;
    /**
     * @var PermissionsRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Tracker_REST_TrackerRestBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tracker_rest_builder;
    private TimetrackingOverviewRepresentationsBuilder $timetracking_overview_representations_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var Project&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin_dao                                     = $this->createMock(\Tuleap\Timetracking\Admin\AdminDao::class);
        $this->permissions_retriever                         = $this->createMock(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);
        $this->tracker_factory                               = $this->createMock(\TrackerFactory::class);
        $this->tracker_rest_builder                          = $this->createMock(\Tracker_REST_TrackerRestBuilder::class);
        $this->timetracking_overview_representations_builder = new TimetrackingOverviewRepresentationsBuilder(
            $this->admin_dao,
            $this->permissions_retriever,
            $this->tracker_factory,
            $this->tracker_rest_builder
        );

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(102);

        $this->project = $this->createMock(Project::class);
        $this->project->method('getID')->willReturn(101);
        $this->project->method('getPublicName')->willReturn('project01');
        $this->project->method('getIconUnicodeCodepoint')->willReturn('');

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(16);
        $this->tracker->method('getName')->willReturn('tracker name');
        $this->tracker->method('getColor')->willReturn(ItemColor::default());
        $this->tracker->method('getProject')->willReturn($this->project);
    }

    public function testGetTrackersMinimalRepresentationWithTimetracking(): void
    {
        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->with(
            $this->user,
            $this->tracker
        )->willReturn(true);
        $this->admin_dao->method('foundRows')->willReturn(1);
        $this->admin_dao->method('getProjectTrackersWithEnabledTimetracking')->willReturn(
            [
                ['tracker_id' => 16],
            ]
        );

        $this->tracker_factory->method('getTrackerById')->with(16)->willReturn($this->tracker);

        $this->tracker->method('userCanView')->with($this->user)->willReturn(true);
        $result = $this->timetracking_overview_representations_builder->getTrackersMinimalRepresentationsWithTimetracking(
            $this->user,
            $this->project,
            10,
            0
        );

        self::assertEquals($this->tracker->getId(), $result['trackers'][0]->id);
        self::assertEquals(1, $result['total_trackers']);
    }

    public function testGetTrackersFullRepresentationWithTimetracking(): void
    {
        $this->tracker->method('getParent')->willReturn(null);
        $this->tracker->method('getUri')->willReturn('');
        $this->tracker->method('getDescription')->willReturn('');
        $this->tracker->method('getItemName')->willReturn('tracker01');
        $this->tracker->method('isNotificationStopped')->willReturn(false);

        $tracker_representation = CompleteTrackerRepresentation::build(
            $this->tracker,
            [],
            [],
            [],
            null
        );

        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->with(
            $this->user,
            $this->tracker
        )->willReturn(true);

        $this->admin_dao->method('foundRows')->willReturn(1);
        $this->admin_dao->method('getProjectTrackersWithEnabledTimetracking')->willReturn(
            [
                ['tracker_id' => 16],
            ]
        );

        $this->tracker_factory->method('getTrackerById')->with(16)->willReturn($this->tracker);
        $this->tracker_rest_builder->method('getTrackerRepresentationInTrackerContext')->with(
            $this->user,
            $this->tracker
        )->willReturn($tracker_representation);

        $this->tracker->method('userCanView')->with($this->user)->willReturn(true);
        $result = $this->timetracking_overview_representations_builder->getTrackersFullRepresentationsWithTimetracking(
            $this->user,
            $this->project,
            10,
            0
        );

        self::assertEquals($tracker_representation->id, $result['trackers'][0]->id);
        self::assertEquals(1, $result['total_trackers']);
    }
}
