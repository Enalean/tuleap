<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToDeleteException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToEditException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotBelongToUserException;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimeUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \Tuleap\Timetracking\Permissions\PermissionsRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_retriever;
    /**
     * @var TimeDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $time_dao;
    private TimeChecker $time_checker;
    private TimeUpdater $time_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    public function setUp(): void
    {
        parent::setUp();

        $this->permissions_retriever = $this->createMock(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);
        $this->time_dao              = $this->createMock(TimeDao::class);
        $this->time_checker          = new TimeChecker();
        $this->time_updater          = new TimeUpdater($this->time_dao, $this->time_checker, $this->permissions_retriever);

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(102);

        $this->tracker = $this->createMock(Tracker::class);

        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->artifact->method('getId')->willReturn(200);
    }

    public function testItThrowsTimeTrackingNotAllowedToAddExceptionToAddTimeIfUserCantAddTime(): void
    {
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(false);
        $this->expectException(TimeTrackingNotAllowedToAddException::class);

        $this->time_dao->expects($this->never())->method('addTime');

        $this->time_updater->addTimeForUserInArtifact($this->user, $this->artifact, '2018-07-19', '11:11', 'oui');
    }

    public function testItThrowsAnExceptionIfTimeIsEmptyInCreation(): void
    {
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);
        $this->expectException(TimeTrackingMissingTimeException::class);

        $this->time_dao->expects($this->never())->method('addTime');

        $this->time_updater->addTimeForUserInArtifact($this->user, $this->artifact, '2018-07-19', '', 'oui');
    }

    public function testItAddsTime(): void
    {
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);

        $this->time_dao
            ->expects($this->once())
            ->method('addTime')
            ->with(102, 200, '2018-07-19', 671, 'oui');

        $this->time_updater->addTimeForUserInArtifact($this->user, $this->artifact, '2018-07-19', '11:11', 'oui');
    }

    public function testItThrowsAnExceptionIfUserCantEditTime(): void
    {
        $time = new Time(1, 102, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(false);
        $this->expectException(TimeTrackingNotAllowedToEditException::class);

        $this->time_dao->expects($this->never())->method('updateTime');

        $this->time_updater->updateTime($this->user, $this->artifact, $time, '2018-07-19', '11:12', 'step');
    }

    public function testItThrowsAnExceptionIfTimeIsEmptyInEdition(): void
    {
        $time = new Time(1, 102, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);
        $this->expectException(TimeTrackingMissingTimeException::class);

        $this->time_dao->expects($this->never())->method('updateTime');

        $this->time_updater->updateTime($this->user, $this->artifact, $time, '2018-07-19', '', 'step');
    }

    public function testItThrowsAnExceptionIfTimeDoesNotBelongToUserInEdition(): void
    {
        $time = new Time(1, 103, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);
        $this->expectException(TimeTrackingNotBelongToUserException::class);

        $this->time_dao->expects($this->never())->method('updateTime');

        $this->time_updater->updateTime($this->user, $this->artifact, $time, '2018-07-19', '11:12', 'step');
    }

    public function testItEditsTime(): void
    {
        $time = new Time(1, 102, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);

        $this->time_dao
            ->expects($this->once())
            ->method('updateTime')
            ->with(1, '2018-07-19', 672, 'step');

        $this->time_updater->updateTime($this->user, $this->artifact, $time, '2018-07-19', '11:12', 'step');
    }

    public function testItThrowsAnExceptionIfUserCantDelete(): void
    {
        $time = new Time(1, 102, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(false);
        $this->expectException(TimeTrackingNotAllowedToDeleteException::class);

        $this->time_dao->expects($this->never())->method('deleteTime');

        $this->time_updater->deleteTime($this->user, $this->artifact, $time);
    }

    public function testItThrowsAnExceptionIfTimeDoesNotBelongToUserInDeletion(): void
    {
        $time = new Time(1, 103, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);
        $this->expectException(TimeTrackingNotBelongToUserException::class);

        $this->time_dao->expects($this->never())->method('deleteTime');

        $this->time_updater->deleteTime($this->user, $this->artifact, $time);
    }

    public function testItDeletesTime(): void
    {
        $time = new Time(1, 102, 200, '2018-07-19', 671, 'step');

        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);

        $this->time_dao
            ->expects($this->once())
            ->method('deleteTime')
            ->with(1);

        $this->time_updater->deleteTime($this->user, $this->artifact, $time);
    }
}
