<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Comment;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentPresenterTest extends TestCase
{
    use GlobalLanguageMock;

    private MockObject&UserHelper $user_helper;

    protected function setUp(): void
    {
        $this->user_helper = $this->createMock(UserHelper::class);
        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
        $this->user_helper->expects(self::once())->method('getLinkOnUserFromUserId')
            ->with(101)
            ->willReturn('<a href="https://example.com">A user</a>');
    }

    public function testItBuildsACommentPresenter(): void
    {
        $comment   = $this->buildComment('Some text content', 'text', 0);
        $presenter = new CommentPresenter($comment, $this->user_helper, UserTestBuilder::aUser()->build());

        self::assertFalse($presenter->has_parent);
        self::assertSame('text', $presenter->format);
        self::assertSame('Some text content', $presenter->purified_body);
        self::assertSame(102, $presenter->changeset_id);
        self::assertFalse($presenter->is_empty);
        self::assertFalse($presenter->was_cleared);
        self::assertFalse($presenter->is_commonmark);
    }

    public function testItBuildsACommonmarkComment(): void
    {
        $comment   = $this->buildComment('Some **Markdown** content', 'commonmark', 0);
        $presenter = new CommentPresenter($comment, $this->user_helper, UserTestBuilder::aUser()->build());

        self::assertSame('commonmark', $presenter->format);
        self::assertSame('Some **Markdown** content', $presenter->commonmark_source);
        self::assertNotEmpty($presenter->purified_body);
        self::assertTrue($presenter->is_commonmark);
    }

    public function testItBuildsAnEmptyComment(): void
    {
        $comment   = $this->buildComment('', 'text', 0);
        $presenter = new CommentPresenter($comment, $this->user_helper, UserTestBuilder::aUser()->build());

        self::assertTrue($presenter->is_empty);
    }

    public function testItBuildsAClearedComment(): void
    {
        $comment   = $this->buildComment('', 'text', 87);
        $presenter = new CommentPresenter($comment, $this->user_helper, UserTestBuilder::aUser()->build());

        self::assertTrue($presenter->is_empty);
        self::assertTrue($presenter->was_cleared);
    }

    private function buildComment(string $body, string $format, int $parent_id): Tracker_Artifact_Changeset_Comment
    {
        $tracker_id = 15;
        $tracker    = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject(
            ProjectTestBuilder::aProject()->withId(110)->build()
        )->build();

        $submitter_user_id = 101;
        $artifact          = new Artifact(48, $tracker_id, $submitter_user_id, 1234567890, false);
        $artifact->setTracker($tracker);
        $changeset_id = '102';

        $changeset_submission_timestamp = 1234567891;
        $changeset                      = new Tracker_Artifact_Changeset(
            $changeset_id,
            $artifact,
            $submitter_user_id,
            $changeset_submission_timestamp,
            null
        );
        return new Tracker_Artifact_Changeset_Comment(
            $changeset_id,
            $changeset,
            null,
            null,
            $submitter_user_id,
            $changeset_submission_timestamp,
            $body,
            $format,
            $parent_id,
            []
        );
    }
}
