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
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const CHANGESET_ID         = 4950;
    private const BODY                 = 'linolate _cysteinic_';
    private const SUBMISSION_TIMESTAMP = 1473016822;
    private \PFUser $submitter;
    /**
     * @var \ProjectUGroup[]
     */
    private array $ugroups_that_are_allowed_to_see;
    private CommentFormatIdentifier $format;

    protected function setUp(): void
    {
        $this->submitter = UserTestBuilder::aUser()->withId(112)->build();

        $this->ugroups_that_are_allowed_to_see = [
            ProjectUGroupTestBuilder::aCustomUserGroup(242)->build(),
            ProjectUGroupTestBuilder::aCustomUserGroup(261)->build(),
        ];

        $this->format = CommentFormatIdentifier::COMMONMARK;
    }

    private function create(): CommentCreation
    {
        $comment = NewComment::fromParts(
            self::BODY,
            $this->format,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $this->ugroups_that_are_allowed_to_see
        );
        return CommentCreation::fromNewComment($comment, self::CHANGESET_ID, new CreatedFileURLMapping());
    }

    public function testItBuildsFromNewComment(): void
    {
        $comment_creation = $this->create();
        self::assertSame(self::CHANGESET_ID, $comment_creation->getChangesetId());
        self::assertSame(self::BODY, $comment_creation->getBody());
        self::assertSame($this->format, $comment_creation->getFormat());
        self::assertSame($this->submitter, $comment_creation->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment_creation->getSubmissionTimestamp());
        self::assertSame($this->ugroups_that_are_allowed_to_see, $comment_creation->getUserGroupsThatAreAllowedToSee());
    }

    public function testItReplacesURLsInHTMLComment(): void
    {
        $body    = '<p>aldime cashkeeper<img src="/replace-me.png"/></p>';
        $mapping = new CreatedFileURLMapping();
        $mapping->add('/replace-me.png', '/replaced.png');
        $comment          = NewComment::fromParts(
            $body,
            CommentFormatIdentifier::HTML,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $this->ugroups_that_are_allowed_to_see
        );
        $comment_creation = CommentCreation::fromNewComment($comment, self::CHANGESET_ID, $mapping);
        self::assertStringContainsString('/replaced.png', $comment_creation->getBody());
    }
}
