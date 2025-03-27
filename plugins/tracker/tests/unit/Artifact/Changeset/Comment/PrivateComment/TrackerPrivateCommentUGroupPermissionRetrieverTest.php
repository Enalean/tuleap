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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerPrivateCommentUGroupPermissionRetrieverTest extends TestCase
{
    private TrackerPrivateCommentUGroupPermissionDao&MockObject $permission_dao;
    private RetrieveTrackerPrivateCommentInformation&MockObject $tracker_private_comment_information_retriever;
    private UGroupManager&MockObject $ugroup_manager;
    private TrackerPrivateCommentUGroupPermissionRetriever $retriever;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->permission_dao = $this->createMock(TrackerPrivateCommentUGroupPermissionDao::class);
        $this->ugroup_manager = $this->createMock(UGroupManager::class);

        $this->tracker = TrackerTestBuilder::aTracker()->withId(15)->build();

        $this->tracker_private_comment_information_retriever = $this->createMock(RetrieveTrackerPrivateCommentInformation::class);

        $this->retriever = new TrackerPrivateCommentUGroupPermissionRetriever(
            $this->permission_dao,
            $this->tracker_private_comment_information_retriever,
            $this->ugroup_manager
        );
    }

    public function testReturnsNullIfTrackerDoesNotUsePrivateComment(): void
    {
        $this->permission_dao->expects(self::never())->method('getUgroupIdsOfPrivateComment');
        $this->tracker_private_comment_information_retriever->expects($this->once())
            ->method('doesTrackerAllowPrivateComments')->with($this->tracker)->willReturn(false);
        $this->ugroup_manager->expects(self::never())->method('getById');

        $ugroups = $this->retriever->getUGroupsCanSeePrivateComment($this->tracker, 5);

        self::assertNull($ugroups);
    }

    public function testReturnsNullIfThereIsNotUGroup(): void
    {
        $this->permission_dao->expects($this->once())->method('getUgroupIdsOfPrivateComment')->with(5)->willReturn([]);
        $this->ugroup_manager->expects(self::never())->method('getById');
        $this->tracker_private_comment_information_retriever->expects($this->once())
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);

        $ugroups = $this->retriever->getUGroupsCanSeePrivateComment($this->tracker, 5);

        self::assertNull($ugroups);
    }

    public function testReturnsArrayOfUgroupsIfTheyExist(): void
    {
        $this->permission_dao->expects($this->once())
            ->method('getUgroupIdsOfPrivateComment')
            ->with(5)
            ->willReturn([1, 2]);
        $this->tracker_private_comment_information_retriever->expects($this->once())
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);

        $ugroup_1 = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2 = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();

        $this->ugroup_manager->expects(self::exactly(2))->method('getById')->willReturnCallback(static fn(int $id) => match ($id) {
            1 => $ugroup_1,
            2 => $ugroup_2,
        });

        $ugroups = $this->retriever->getUGroupsCanSeePrivateComment($this->tracker, 5);

        self::assertCount(2, $ugroups);
        self::assertEquals($ugroup_1, $ugroups[0]);
        self::assertEquals($ugroup_2, $ugroups[1]);
    }
}
