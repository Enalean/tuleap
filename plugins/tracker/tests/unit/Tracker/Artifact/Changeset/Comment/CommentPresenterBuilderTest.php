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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;

final class CommentPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PermissionChecker
     */
    private $permission_checker;
    /**
     * @var CommentPresenterBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact_Changeset_Comment
     */
    private $comment;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserHelper
     */
    private $user_helper;

    protected function setUp(): void
    {
        $this->permission_checker = \Mockery::mock(PermissionChecker::class);

        $this->user = \Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getPreference');
        $this->user->shouldReceive('getLocale');

        $this->comment = $this->buildComment('My body');

        $this->user_helper = \Mockery::spy(\UserHelper::class);

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');

        $this->builder = new CommentPresenterBuilder($this->permission_checker, $this->user_helper);
    }

    public function testGetNullIfCommentIsEmpty(): void
    {
        $this->comment = $this->buildComment('');
        $presenter     = $this->builder->getCommentPresenter($this->comment, $this->user);
        $this->assertNull($presenter);
    }

    public function testGetNullIfUserCanNotSeeComment(): void
    {
        $this->permission_checker->shouldReceive('isPrivateCommentForUser')
            ->with($this->user, $this->comment)
            ->once()
            ->andReturnTrue();
        $presenter = $this->builder->getCommentPresenter($this->comment, $this->user);
        $this->assertNull($presenter);
    }

    public function testGetCommentPresenterIfUserCanSeeCommentAndItIsNotEmpty(): void
    {
        $this->permission_checker->shouldReceive('isPrivateCommentForUser')
            ->with($this->user, $this->comment)
            ->once()
            ->andReturnFalse();
        $presenter = $this->builder->getCommentPresenter($this->comment, $this->user);
        $this->assertInstanceOf(CommentPresenter::class, $presenter);
    }

    private function buildComment(string $body): Tracker_Artifact_Changeset_Comment
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(110);
        $tracker->shouldReceive('getProject')->andReturn(ProjectTestBuilder::aProject()->build());
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);
        $changeset->shouldReceive('getId')->andReturn(52);

        return new Tracker_Artifact_Changeset_Comment(
            525,
            $changeset,
            null,
            null,
            110,
            1234567890,
            $body,
            'text',
            0,
            null
        );
    }
}
