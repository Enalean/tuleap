<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Permissions;

use PFUser;
use Tracker;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimetrackingUgroupRetriever
     */
    private $timetracking_ugroup_retriever;
    private PermissionsRetriever $retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timetracking_ugroup_retriever = $this->createMock(TimetrackingUgroupRetriever::class);

        $this->retriever = new PermissionsRetriever(
            $this->timetracking_ugroup_retriever
        );

        $this->user    = $this->createMock(PFUser::class);
        $this->tracker = $this->createMock(Tracker::class);

        $this->tracker->method('getGroupId')->willReturn(101);
        $this->tracker->method('getId')->willReturn(123);
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsSiteAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsProjectAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(false);

        $this->user
            ->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsTrackerAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(false);

        $this->user
            ->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(false);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->with($this->user)
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever
            ->expects(self::once())
            ->method('getReaderIdsForTracker')
            ->with($this->tracker)
            ->willReturn([
                987,
            ]);

        $this->user
            ->expects(self::once())
            ->method('isMemberOfUGroup')
            ->with(987, 101)
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToReadAllTimesIfUserIsNotMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever
            ->expects(self::once())
            ->method('getReaderIdsForTracker')
            ->with($this->tracker)
            ->willReturn([
                987,
            ]);

        $this->user
            ->expects(self::once())
            ->method('isMemberOfUGroup')
            ->with(987, 101)
            ->willReturn(false);

        self::assertFalse(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToReadAllTimesIfUserIsNotAdminAndNoUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever
            ->expects(self::once())
            ->method('getReaderIdsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $this->user->expects(self::never())->method('isMemberOfUGroup');

        self::assertFalse(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsSiteAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsProjectAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(false);

        $this->user
            ->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsTrackerAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(false);

        $this->user
            ->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(false);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->with($this->user)
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever
            ->expects(self::once())
            ->method('getWriterIdsForTracker')
            ->with($this->tracker)
            ->willReturn([
                987,
            ]);

        $this->user
            ->expects(self::once())
            ->method('isMemberOfUGroup')
            ->with(987, 101)
            ->willReturn(true);

        self::assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToAddTimesIfUserIsNotMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever
            ->expects(self::once())
            ->method('getWriterIdsForTracker')
            ->with($this->tracker)
            ->willReturn([
                987,
            ]);

        $this->user
            ->expects(self::once())
            ->method('isMemberOfUGroup')
            ->with(987, 101)
            ->willReturn(false);

        self::assertFalse(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToAddTimesIfUserIsNotAdminAndNoUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever
            ->expects(self::once())
            ->method('getWriterIdsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $this->user->expects(self::never())->method('isMemberOfUGroup');

        self::assertFalse(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    private function mockUserIsNotAdmin(): void
    {
        $this->user
            ->expects(self::once())
            ->method('isSuperUser')
            ->willReturn(false);

        $this->user
            ->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(false);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->with($this->user)
            ->willReturn(false);
    }
}
