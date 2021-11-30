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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;

class PermissionsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PermissionsRetriever
     */
    private $retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimetrackingUgroupRetriever
     */
    private $timetracking_ugroup_retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timetracking_ugroup_retriever = Mockery::mock(TimetrackingUgroupRetriever::class);

        $this->retriever = new PermissionsRetriever(
            $this->timetracking_ugroup_retriever
        );

        $this->user    = Mockery::mock(PFUser::class);
        $this->tracker = Mockery::mock(Tracker::class);

        $this->tracker->shouldReceive('getGroupId')->andReturn(101);
        $this->tracker->shouldReceive('getId')->andReturn(123);
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsSiteAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsProjectAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnFalse();

        $this->user->shouldReceive('isAdmin')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsTrackerAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnFalse();

        $this->user->shouldReceive('isAdmin')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->tracker->shouldReceive('userIsAdmin')
            ->once()
            ->with($this->user)
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToReadAllTimesIfUserIsMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever->shouldReceive('getReaderIdsForTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn([
                987,
            ]);

        $this->user->shouldReceive('isMemberOfUGroup')
            ->once()
            ->with(987, 101)
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToReadAllTimesIfUserIsNotMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever->shouldReceive('getReaderIdsForTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn([
                987,
            ]);

        $this->user->shouldReceive('isMemberOfUGroup')
            ->once()
            ->with(987, 101)
            ->andReturnFalse();

        $this->assertFalse(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToReadAllTimesIfUserIsNotAdminAndNoUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever->shouldReceive('getReaderIdsForTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn([]);

        $this->user->shouldNotReceive('isMemberOfUGroup');

        $this->assertFalse(
            $this->retriever->userCanSeeAllTimesInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsSiteAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsProjectAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnFalse();

        $this->user->shouldReceive('isAdmin')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsTrackerAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnFalse();

        $this->user->shouldReceive('isAdmin')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->tracker->shouldReceive('userIsAdmin')
            ->once()
            ->with($this->user)
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsTrueToAddTimesIfUserIsMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever->shouldReceive('getWriterIdsForTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn([
                987,
            ]);

        $this->user->shouldReceive('isMemberOfUGroup')
            ->once()
            ->with(987, 101)
            ->andReturnTrue();

        $this->assertTrue(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToAddTimesIfUserIsNotMemberOfAUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever->shouldReceive('getWriterIdsForTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn([
                987,
            ]);

        $this->user->shouldReceive('isMemberOfUGroup')
            ->once()
            ->with(987, 101)
            ->andReturnFalse();

        $this->assertFalse(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    public function testItReturnsFalseToAddTimesIfUserIsNotAdminAndNoUgroupGrantedToRead(): void
    {
        $this->mockUserIsNotAdmin();

        $this->timetracking_ugroup_retriever->shouldReceive('getWriterIdsForTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn([]);

        $this->user->shouldNotReceive('isMemberOfUGroup');

        $this->assertFalse(
            $this->retriever->userCanAddTimeInTracker(
                $this->user,
                $this->tracker
            )
        );
    }

    private function mockUserIsNotAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')
            ->once()
            ->andReturnFalse();

        $this->user->shouldReceive('isAdmin')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->tracker->shouldReceive('userIsAdmin')
            ->once()
            ->with($this->user)
            ->andReturnFalse();
    }
}
