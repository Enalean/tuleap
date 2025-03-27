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

namespace Tuleap\Tracker\Artifact\Changeset\Comment;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentPresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private PermissionChecker&MockObject $permission_checker;
    private CommentPresenterBuilder $builder;
    private PFUser $user;
    private Tracker_Artifact_Changeset_Comment $comment;

    protected function setUp(): void
    {
        $this->permission_checker = $this->createMock(PermissionChecker::class);
        $this->user               = UserTestBuilder::buildWithDefaults();
        $this->comment            = $this->buildComment('My body');

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');

        $user_helper = $this->createStub(UserHelper::class);
        $user_helper->method('getLinkOnUserFromUserId')->willReturn('/some/user/link');

        $this->builder = new CommentPresenterBuilder($this->permission_checker, $user_helper);
    }

    public function testGetNullIfCommentIsEmpty(): void
    {
        $this->comment = $this->buildComment('');
        $presenter     = $this->builder->getCommentPresenter($this->comment, $this->user);
        self::assertNull($presenter);
    }

    public function testGetNullIfUserCanNotSeeComment(): void
    {
        $this->permission_checker->expects($this->once())->method('isPrivateCommentForUser')
            ->with($this->user, $this->comment)
            ->willReturn(true);
        $presenter = $this->builder->getCommentPresenter($this->comment, $this->user);
        self::assertNull($presenter);
    }

    public function testGetCommentPresenterIfUserCanSeeCommentAndItIsNotEmpty(): void
    {
        $this->permission_checker->expects($this->once())->method('isPrivateCommentForUser')
            ->with($this->user, $this->comment)
            ->willReturn(false);
        $presenter = $this->builder->getCommentPresenter($this->comment, $this->user);
        self::assertInstanceOf(CommentPresenter::class, $presenter);
    }

    private function buildComment(string $body): Tracker_Artifact_Changeset_Comment
    {
        $tracker   = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->withId(110)->build())->build();
        $artifact  = ArtifactTestBuilder::anArtifact(458)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset(52)->ofArtifact($artifact)->build();

        return new Tracker_Artifact_Changeset_Comment(
            525,
            $changeset,
            1,
            0,
            110,
            1234567890,
            $body,
            'text',
            0,
            null
        );
    }
}
