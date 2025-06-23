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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionCheckerTest extends TestCase
{
    private PermissionChecker $checker;
    private PFUser&MockObject $user;
    private Tracker_Artifact_Changeset_Comment $comment;
    private Tracker_Artifact_Changeset $changeset;
    private Tracker&MockObject $tracker;
    private RetrieveTrackerPrivateCommentInformation&MockObject $tracker_private_comment_information_retriever;

    protected function setUp(): void
    {
        $this->user = $this->createMock(PFUser::class);

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getGroupId')->willReturn(101);
        $this->tracker->method('getId')->willReturn(200);

        $artifact        = ArtifactTestBuilder::anArtifact(569)->inTracker($this->tracker)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(12)->ofArtifact($artifact)->build();

        $this->comment = $this->buildComment([]);

        $this->tracker_private_comment_information_retriever = $this->createMock(RetrieveTrackerPrivateCommentInformation::class);

        $this->checker = new PermissionChecker($this->tracker_private_comment_information_retriever);
    }

    public function testReturnsFalseIfTrackerDoesNotUsePrivateComment(): void
    {
        $this->tracker_private_comment_information_retriever->expects($this->once())
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(false);

        self::assertFalse($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsFalseIfUserIsSiteAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(true);
        self::assertFalse($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsFalseIfUserIsProjectAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(true);

        self::assertFalse($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsFalseIfUserIsTrackerAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);
        $this->tracker->expects($this->once())->method('userIsAdmin')->willReturn(true);
        self::assertFalse($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsFalseIfUserIsMemberOfStaticUgroupAndNotAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->tracker->method('userIsAdmin')->willReturn(false);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $ugroup_1 = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2 = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3 = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();

        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);

        $this->user->method('isMemberOfUGroup')->willReturnCallback(static fn(int $id) => $id == 2);

        self::assertFalse($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsTrueIfThereAreNoUGroupsButCommentIsPrivate(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->tracker->method('userIsAdmin')->willReturn(false);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $this->user->expects($this->never())->method('isMemberOfUGroup');

        self::assertTrue($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsFalseIfPrivateCommentIsNull(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->tracker->method('userIsAdmin')->willReturn(false);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $this->user->expects($this->never())->method('isMemberOfUGroup');

        $this->comment = $this->buildComment(null);

        self::assertFalse($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testReturnsTrueIfUserIsNotMemberOfStaticUgroupAndNotAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $this->tracker->method('userIsAdmin')->willReturn(false);

        $ugroup_1 = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2 = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3 = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();

        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);

        $this->user->method('isMemberOfUGroup')->with(self::callback(static fn(int $id) => in_array($id, [1, 2, 3])), 101)->willReturn(false);

        self::assertTrue($this->checker->isPrivateCommentForUser($this->user, $this->comment));
    }

    public function testGetAllUGroupsIfUserIsSiteAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(true);

        $ugroup_1      = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2      = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3      = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();
        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);

        $ugroups = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertIsArray($ugroups);
        self::assertCount(3, $ugroups);
    }

    public function testGetAllUGroupsIfUserIsProjectAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(true);

        $ugroup_1      = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2      = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3      = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();
        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);
        $ugroups       = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertIsArray($ugroups);
        self::assertCount(3, $ugroups);
    }

    public function testGetAllUGroupsIfUserIsTrackerAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $this->tracker->expects($this->once())->method('userIsAdmin')->willReturn(true);

        $ugroup_1      = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2      = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3      = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();
        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);
        $ugroups       = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertIsArray($ugroups);
        self::assertCount(3, $ugroups);
    }

    public function testGetUGroupsThatUserIsMemberOfAndUserIsNotAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $this->tracker->method('userIsAdmin')->willReturn(false);

        $this->user->method('isMemberOfUGroup')
            ->with(self::callback(static fn(int $id) => in_array($id, [1, 2, 3])), 101)
            ->willReturnCallback(static fn(int $id) => in_array($id, [1, 3]));

        $ugroup_1      = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2      = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3      = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();
        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);
        $ugroups       = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertIsArray($ugroups);
        self::assertCount(2, $ugroups);
        self::assertEquals(1, $ugroups[0]->getId());
        self::assertEquals(3, $ugroups[1]->getId());
    }

    public function testGetUserIsNotAllowedToSeeUGroupsIfUserIsNotMemberOfUGroupsAndUserIsNotAdmin(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->once())->method('isSuperUser')->willReturn(false);
        $this->user->expects($this->once())->method('isAdmin')->with(101)->willReturn(false);

        $this->tracker->method('userIsAdmin')->willReturn(false);

        $ugroup_1      = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $ugroup_2      = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $ugroup_3      = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();
        $this->comment = $this->buildComment([$ugroup_1, $ugroup_2, $ugroup_3]);
        $this->user->method('isMemberOfUGroup')
            ->with(self::callback(static fn(int $id) => in_array($id, [1, 2, 3])), 101)
            ->willReturn(false);

        $ugroups = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertInstanceOf(UserIsNotAllowedToSeeUGroups::class, $ugroups);
    }

    public function testGetUserIsNotAllowedToSeeUGroupsIfUGroupsIsNull(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->never())->method('isSuperUser');
        $this->user->expects($this->never())->method('isAdmin')->with(101);

        $this->user->method('isMemberOfUGroup')
            ->with(self::callback(static fn(int $id) => in_array($id, [1, 2, 3])), 101)
            ->willReturn(false);

        $this->comment = $this->buildComment(null);

        $ugroups = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertInstanceOf(UserIsNotAllowedToSeeUGroups::class, $ugroups);
    }

    public function testGetUserIsNotAllowedToSeeUGroupsIfThereAreNoGroups(): void
    {
        $this->tracker_private_comment_information_retriever
            ->method('doesTrackerAllowPrivateComments')
            ->with($this->tracker)
            ->willReturn(true);
        $this->user->expects($this->never())->method('isSuperUser');
        $this->user->expects($this->never())->method('isAdmin')->with(101);

        $this->user->method('isMemberOfUGroup')
            ->with(self::callback(static fn(int $id) => in_array($id, [1, 2, 3])), 101)
            ->willReturn(false);

        $ugroups = $this->checker->getUgroupsThatUserCanSeeOnComment($this->user, $this->comment);

        self::assertInstanceOf(UserIsNotAllowedToSeeUGroups::class, $ugroups);
    }

    /**
     * @param ProjectUGroup[]|null $ugroups
     */
    private function buildComment(?array $ugroups): Tracker_Artifact_Changeset_Comment
    {
        return new Tracker_Artifact_Changeset_Comment(
            525,
            $this->changeset,
            1,
            0,
            110,
            1234567890,
            'A text comment',
            'text',
            0,
            $ugroups
        );
    }
}
