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

namespace Tuleap\Tracker\Artifact\Changeset\Comment;

use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewCommentTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SUBMISSION_TIMESTAMP = 1680808942;
    private \PFUser $submitter;
    /**
     * @var \ProjectUGroup[]
     */
    private array $ugroups_that_are_allowed_to_see;
    private CommentFormatIdentifier $format;

    protected function setUp(): void
    {
        $this->submitter = UserTestBuilder::aUser()->withId(133)->build();

        $this->ugroups_that_are_allowed_to_see = [
            ProjectUGroupTestBuilder::aCustomUserGroup(291)->build(),
            ProjectUGroupTestBuilder::aCustomUserGroup(241)->build(),
        ];

        $this->format = CommentFormatIdentifier::TEXT;
    }

    private function create(string $body): NewComment
    {
        return NewComment::fromParts(
            $body,
            $this->format,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $this->ugroups_that_are_allowed_to_see
        );
    }

    public function testItBuildsFromParts(): void
    {
        $body    = 'pseudotribal comet';
        $comment = $this->create($body);
        self::assertSame($body, $comment->getBody());
        self::assertSame($this->format, $comment->getFormat());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertSame($this->ugroups_that_are_allowed_to_see, $comment->getUserGroupsThatAreAllowedToSee());
    }

    public function testItTrimsCommentBody(): void
    {
        $comment = $this->create('  chief dorsicollar ');
        self::assertSame('chief dorsicollar', $comment->getBody());
    }

    public function testItBuildsEmptyCommonMarkComment(): void
    {
        $comment = NewComment::buildEmpty($this->submitter, self::SUBMISSION_TIMESTAMP);
        self::assertEmpty($comment->getBody());
        self::assertSame(CommentFormatIdentifier::COMMONMARK, $comment->getFormat());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertCount(0, $comment->getUserGroupsThatAreAllowedToSee());
    }
}
