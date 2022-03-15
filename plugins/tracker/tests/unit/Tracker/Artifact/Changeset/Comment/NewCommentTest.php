<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tracker\Artifact\Changeset\Comment;

use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class NewCommentTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const CHANGESET_ID         = 4950;
    private const SUBMISSION_TIMESTAMP = 1680808942;
    private \PFUser $submitter;
    /**
     * @var \ProjectUGroup[]
     */
    private array $ugroups_that_are_allowed_to_see;

    protected function setUp(): void
    {
        $this->submitter                       = UserTestBuilder::aUser()->withId(133)->build();
        $this->ugroups_that_are_allowed_to_see = [
            ProjectUGroupTestBuilder::aCustomUserGroup(291),
            ProjectUGroupTestBuilder::aCustomUserGroup(241),
        ];
    }

    public function testItBuildsWithTextFormat(): void
    {
        $body    = 'pseudotribal comet';
        $comment = NewComment::fromText(
            self::CHANGESET_ID,
            $body,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $this->ugroups_that_are_allowed_to_see
        );
        self::assertSame(self::CHANGESET_ID, $comment->getChangesetId());
        self::assertSame($body, $comment->getBody());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::TEXT_COMMENT, $comment->getFormat());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertSame($this->ugroups_that_are_allowed_to_see, $comment->getUserGroupsThatAreAllowedToSee());
    }

    public function testItBuildsWithCommonMarkFormat(): void
    {
        $body    = 'carnivorism _Lowville_';
        $comment = NewComment::fromCommonMark(
            self::CHANGESET_ID,
            $body,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $this->ugroups_that_are_allowed_to_see
        );
        self::assertSame(self::CHANGESET_ID, $comment->getChangesetId());
        self::assertSame($body, $comment->getBody());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT, $comment->getFormat());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertSame($this->ugroups_that_are_allowed_to_see, $comment->getUserGroupsThatAreAllowedToSee());
    }

    public function testItReplacesURLsInHTMLComment(): void
    {
        $body    = '<p>aldime cashkeeper<img src="/replace-me.png"/></p>';
        $mapping = new CreatedFileURLMapping();
        $mapping->add('/replace-me.png', '/replaced.png');
        $comment = NewComment::fromHTML(
            self::CHANGESET_ID,
            $body,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $this->ugroups_that_are_allowed_to_see,
            $mapping
        );
        self::assertSame(self::CHANGESET_ID, $comment->getChangesetId());
        self::assertStringContainsString('/replaced.png', $comment->getBody());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::HTML_COMMENT, $comment->getFormat());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertSame($this->ugroups_that_are_allowed_to_see, $comment->getUserGroupsThatAreAllowedToSee());
    }
}
