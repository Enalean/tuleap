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

use Codendi_Request;
use CSRFSynchronizerToken;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Timetracking\Exceptions\TimeTrackingExistingDateException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToDeleteException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToEditException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotBelongToUserException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNoTimeException;

require_once __DIR__ . '/../bootstrap.php';

class TimeControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /*
     * TimeController
     */
    private $time_controller;

    /*
     * Time
     */
    private $time;

    public function setUp()
    {
        parent::setUp();

        $this->time_updater          = \Mockery::spy(\Tuleap\Timetracking\Time\TimeUpdater::class);
        $this->time_retriever        = \Mockery::spy(TimeRetriever::class);
        $this->permissions_retriever = \Mockery::spy(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);
        $this->request               = \Mockery::spy(Codendi_Request::class);
        $this->time_checker          = \Mockery::spy(TimeChecker::class);
        $this->time_controller       = new TimeController($this->permissions_retriever, $this->time_updater, $this->time_retriever, $this->time_checker);

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->allows()->getId()->andReturns(102);

        $this->tracker  = \Mockery::spy(Tracker::class);
        $this->time     = new Time(83, 102, 2, '2018-04-04', 81, 'g');
        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);

        $this->tracker->shouldReceive([
            'getGroupId' => 102,
            'getId'      => 16
        ]);

        $this->artifact->shouldReceive([
            'getTracker' => $this->tracker,
            'getId'      => 200
        ]);

        $this->url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'aid'  => $this->artifact->getId(),
                'view' => 'timetracking'
         ));

        $this->request->allows()->get('time-id')->andReturns(83);

        $this->csrf = \Mockery::spy(CSRFSynchronizerToken::class);
        $this->csrf->allows()->check()->andReturns(true);
    }

    public function testItThrowsNothingIfAddTimeSuccess()
    {
        $this->request->allows()->get('timetracking-new-time-step')->andReturns('step');
        $this->request->allows()->get('timetracking-new-time-time')->andReturns('01:21');
        $this->request->allows()->get('timetracking-new-time-date')->andReturns('2018-04-04');

        $this->time_checker->allows()->checkMandatoryTimeValue('01:21')->andReturns(true);
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->time_checker->allows()->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-05-25')->andReturns(null);

        $this->time_updater->shouldReceive('addTimeForUserInArtifact')->Once();
        $this->assertNull($this->time_controller->addTimeForUser($this->request, $this->user, $this->artifact, $this->csrf));
    }

    public function testItThrowsTimeTrackingNotAllowedToAddExceptionToAddTimeIfUserCantAddTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);
        $this->expectException(TimeTrackingNotAllowedToAddException::class);

        $this->time_controller->addTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItThrowsTimeTrackingMissingTimeToAddTimeIfTimeNull()
    {
        $this->request->allows()->get('timetracking-new-time-time')->andReturns(null);
        $this->request->allows()->get('timetracking-new-time-date')->andReturns('2018-04-04');
        $this->request->allows()->get('timetracking-new-time-step')->andReturns('step');

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);

        $this->expectException(TimeTrackingMissingTimeException::class);

        $this->time_controller->addTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItThrowsTimeTrackingExistingDateExceptionIfExistingDateToAddTime()
    {
        $this->time_checker->allows()->checkMandatoryTimeValue('01:21')->andReturns(true);
        $this->time_checker->allows()->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-04-04')->andReturns($this->time);

        $this->request->allows()->get('timetracking-new-time-time')->andReturns('01:21');
        $this->request->allows()->get('timetracking-new-time-date')->andReturns('2018-04-04');
        $this->request->allows()->get('timetracking-new-time-step')->andReturns('step');

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->expectException(TimeTrackingExistingDateException::class);

        $this->time_controller->addTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItThrowsNothingIfDeleteTimeSuccess()
    {
        $this->request->allows()->get('timetracking-new-time-step')->andReturns('step');
        $this->request->allows()->get('timetracking-new-time-time')->andReturns('01:21');
        $this->request->allows()->get('timetracking-new-time-date')->andReturns('2018-04-04');
        $this->time_checker->allows()->checkMandatoryTimeValue('01:21')->andReturns(true);

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns($this->time);

        $this->time_updater->shouldReceive('deleteTime')->Once();

        $this->assertNull($this->time_controller->deleteTimeForUser($this->request, $this->user, $this->artifact, $this->csrf));
    }

    public function testItThrowsTimeTrackingNotAllowedToDeleteTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);
        $this->expectException(TimeTrackingNotAllowedToDeleteException::class);

        $this->time_controller->deleteTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItThrowsTimeTrackingNoTimeExceptionToDeleteTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns(null);
        $this->expectException(TimeTrackingNoTimeException::class);

        $this->time_controller->deleteTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function itThrowsTimeTrackingNotBelongToUserExceptionToDeleteTime()
    {
        $user = aUser()->withId(103)->build();
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($user, $this->time->getId())->andReturns($this->time);
        $this->time_checker->allows()->doesTimeBelongsToUser($this->time, $user)->andReturns(true);

        $this->expectException(TimeTrackingNotBelongToUserException::class);

        $this->time_controller->deleteTimeForUser($this->request, $user, $this->artifact, $this->csrf);
    }

    public function testItThrowsNothingIfEditTimeSuccess()
    {
        $this->request->allows()->get('timetracking-edit-time-step')->andReturns('step');
        $this->request->allows()->get('timetracking-edit-time-time')->andReturns('01:21');
        $this->request->allows()->get('timetracking-edit-time-date')->andReturns('2018-04-04');
        $this->time_checker->allows()->checkMandatoryTimeValue('01:21')->andReturns(true);

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns($this->time);

        $this->time_updater->shouldReceive('updateTime')->Once();

        $this->assertNull($this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf));
    }

    public function testItTrowsTimeTrackingNotAllowedToEditExceptionToEditTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);
        $this->expectException(TimeTrackingNotAllowedToEditException::class);

        $this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItTrowsTimeTrackingNoTimeExceptionToEditTime()
    {
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns(null);
        $this->expectException(TimeTrackingNoTimeException::class);

        $this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItTrowsTimeTrackingNotBelongToUserExceptionToEditTime()
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->allows()->getId()->andReturns(103);

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($user, $this->time->getId())->andReturns($this->time);
        $this->time_checker->allows()->doesTimeBelongsToUser($this->time, $user)->andReturns(true);
        $this->expectException(TimeTrackingNotBelongToUserException::class);

        $this->time_controller->editTimeForUser($this->request, $user, $this->artifact, $this->csrf);
    }

    public function testItTrowsTimeTrackingMissingTimeExceptionToEditTime()
    {
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns($this->time);

        $this->request->allows()->get('timetracking-edit-time-time')->andReturns(null);
        $this->request->allows()->get('timetracking-edit-time-date')->andReturns('2018-04-04');
        $this->request->allows()->get('timetracking-edit-time-step')->andReturns('step');

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);

        $this->expectException(TimeTrackingMissingTimeException::class);

        $this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItTrowsTimeTrackingExistingDateExceptionToEditTime()
    {
        $this->time_checker->allows()->checkMandatoryTimeValue('01:21')->andReturns(true);
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns($this->time);
        $this->time_checker->allows()->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-05-04')->andReturns($this->time);

        $this->request->allows()->get('timetracking-edit-time-time')->andReturns('01:21');
        $this->request->allows()->get('timetracking-edit-time-date')->andReturns('2018-05-04');
        $this->request->allows()->get('timetracking-edit-time-step')->andReturns('step');

        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->expectException(TimeTrackingExistingDateException::class);

        $this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }
}
