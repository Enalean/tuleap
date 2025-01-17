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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class TrackerPrivateCommentUGroupPermissionRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerPrivateCommentUGroupPermissionDao
     */
    private $permission_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RetrieveTrackerPrivateCommentInformation
     */
    private $tracker_private_comment_information_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var TrackerPrivateCommentUGroupPermissionRetriever
     */
    private $retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->permission_dao = \Mockery::mock(TrackerPrivateCommentUGroupPermissionDao::class);
        $this->ugroup_manager = \Mockery::mock(\UGroupManager::class);

        $this->tracker = \Mockery::mock(\Tracker::class, ['getId' => 15]);

        $this->tracker_private_comment_information_retriever = \Mockery::mock(RetrieveTrackerPrivateCommentInformation::class);
        $this->tracker_private_comment_information_retriever
            ->shouldReceive('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->once()
            ->andReturnTrue()
            ->byDefault();

        $this->retriever = new TrackerPrivateCommentUGroupPermissionRetriever(
            $this->permission_dao,
            $this->tracker_private_comment_information_retriever,
            $this->ugroup_manager
        );
    }

    public function testReturnsNullIfTrackerDoesNotUsePrivateComment(): void
    {
        $this->permission_dao->shouldReceive('getUgroupIdsOfPrivateComment')->never();
        $this->tracker_private_comment_information_retriever->shouldReceive('doesTrackerAllowPrivateComments')->with($this->tracker)->once()->andReturnFalse();
        $this->ugroup_manager->shouldReceive('getById')->never();

        $ugroups = $this->retriever->getUGroupsCanSeePrivateComment($this->tracker, 5);

        self::assertNull($ugroups);
    }

    public function testReturnsNullIfThereIsNotUGroup(): void
    {
        $this->permission_dao->shouldReceive('getUgroupIdsOfPrivateComment')->with(5)->once()->andReturn([]);
        $this->ugroup_manager->shouldReceive('getById')->never();

        $ugroups = $this->retriever->getUGroupsCanSeePrivateComment($this->tracker, 5);

        self::assertNull($ugroups);
    }

    public function testReturnsArrayOfUgroupsIfTheyExist(): void
    {
        $this->permission_dao
            ->shouldReceive('getUgroupIdsOfPrivateComment')
            ->with(5)
            ->once()
            ->andReturn([1, 2]);

        $ugroup_1 = \Mockery::mock(\ProjectUGroup::class);
        $ugroup_2 = \Mockery::mock(\ProjectUGroup::class);

        $this->ugroup_manager
            ->shouldReceive('getById')
            ->with(1)
            ->once()
            ->andReturn($ugroup_1);
        $this->ugroup_manager
            ->shouldReceive('getById')
            ->with(2)
            ->once()
            ->andReturn($ugroup_2);

        $ugroups = $this->retriever->getUGroupsCanSeePrivateComment($this->tracker, 5);

        self::assertCount(2, $ugroups);
        self::assertEquals($ugroup_1, $ugroups[0]);
        self::assertEquals($ugroup_2, $ugroups[1]);
    }
}
