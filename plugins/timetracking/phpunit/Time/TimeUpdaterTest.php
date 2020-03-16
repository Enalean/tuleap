<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Time;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToDeleteException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToEditException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotBelongToUserException;

require_once __DIR__ . '/../bootstrap.php';

class TimeUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TimeUpdater
     */
    private $time_updater;

    public function setUp() : void
    {
        parent::setUp();

        $this->permissions_retriever = \Mockery::mock(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);
        $this->time_dao              = \Mockery::mock(TimeDao::class);
        $this->time_checker          = new TimeChecker();
        $this->time_updater          = new TimeUpdater($this->time_dao, $this->time_checker, $this->permissions_retriever);

        $this->user = \Mockery::mock(\PFUser::class);
        $this->user->allows()->getId()->andReturns(102);

        $this->tracker = \Mockery::spy(Tracker::class);

        $this->artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->artifact->shouldReceive([
            'getTracker' => $this->tracker,
            'getId'      => 200
        ]);
    }

    public function testItThrowsTimeTrackingNotAllowedToAddExceptionToAddTimeIfUserCantAddTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);
        $this->expectException(TimeTrackingNotAllowedToAddException::class);

        $this->time_dao->shouldReceive('addTime')->never();

        $this->time_updater->addTimeForUserInArtifact($this->user, $this->artifact, "2018-07-19", "11:11", "oui");
    }

    public function testItThrowsAnExceptionIfTimeIsEmptyInCreation()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->expectException(TimeTrackingMissingTimeException::class);

        $this->time_dao->shouldReceive('addTime')->never();

        $this->time_updater->addTimeForUserInArtifact($this->user, $this->artifact, "2018-07-19", "", "oui");
    }

    public function testItAddsTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);

        $this->time_dao->shouldReceive('addTime')
            ->with(102, 200, "2018-07-19", 671, "oui")
            ->once();

        $this->time_updater->addTimeForUserInArtifact($this->user, $this->artifact, "2018-07-19", "11:11", "oui");
    }

    public function testItThrowsAnExceptionIfUserCantEditTime()
    {
        $time = new Time(1, 102, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);
        $this->expectException(TimeTrackingNotAllowedToEditException::class);

        $this->time_dao->shouldReceive('updateTime')->never();

        $this->time_updater->updateTime($this->user, $this->artifact, $time, "2018-07-19", "11:12", "step");
    }

    public function testItThrowsAnExceptionIfTimeIsEmptyInEdition()
    {
        $time = new Time(1, 102, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->expectException(TimeTrackingMissingTimeException::class);

        $this->time_dao->shouldReceive('updateTime')->never();

        $this->time_updater->updateTime($this->user, $this->artifact, $time, "2018-07-19", "", "step");
    }

    public function testItThrowsAnExceptionIfTimeDoesNotBelongToUserInEdition()
    {
        $time = new Time(1, 103, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->expectException(TimeTrackingNotBelongToUserException::class);

        $this->time_dao->shouldReceive('updateTime')->never();

        $this->time_updater->updateTime($this->user, $this->artifact, $time, "2018-07-19", "11:12", "step");
    }

    public function testItEditsTime()
    {
        $time = new Time(1, 102, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);

        $this->time_dao->shouldReceive('updateTime')
           ->with(1, "2018-07-19", 672, "step")
           ->once();

        $this->time_updater->updateTime($this->user, $this->artifact, $time, "2018-07-19", "11:12", "step");
    }

    public function testItThrowsAnExceptionIfUserCantDelete()
    {
        $time = new Time(1, 102, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);
        $this->expectException(TimeTrackingNotAllowedToDeleteException::class);

        $this->time_dao->shouldReceive('deleteTime')->never();

        $this->time_updater->deleteTime($this->user, $this->artifact, $time);
    }

    public function testItThrowsAnExceptionIfTimeDoesNotBelongToUserInDeletion()
    {
        $time = new Time(1, 103, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->expectException(TimeTrackingNotBelongToUserException::class);

        $this->time_dao->shouldReceive('deleteTime')->never();

        $this->time_updater->deleteTime($this->user, $this->artifact, $time);
    }

    public function testItDeletesTime()
    {
        $time = new Time(1, 102, 200, "2018-07-19", 671, "step");

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);

        $this->time_dao->shouldReceive('deleteTime')
           ->with(1)
           ->once();

        $this->time_updater->deleteTime($this->user, $this->artifact, $time);
    }
}
