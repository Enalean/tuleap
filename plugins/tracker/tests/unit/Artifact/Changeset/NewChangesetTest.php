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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SUBMISSION_TIMESTAMP = 1908282639;
    private Artifact $artifact;
    private \PFUser $submitter;
    private array $fields_data;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact    = ArtifactTestBuilder::anArtifact(63)->build();
        $this->submitter   = UserTestBuilder::aUser()->withId(197)->build();
        $this->fields_data = [279 => 'fake fields data'];
    }

    public function testItBuildsFromFieldsDataArray(): void
    {
        $comment_body                    = 'nonpersecution rotundiform';
        $comment_format                  = CommentFormatIdentifier::COMMONMARK;
        $ugroups_that_are_allowed_to_see = [
            ProjectUGroupTestBuilder::aCustomUserGroup(116)->build(),
            ProjectUGroupTestBuilder::aCustomUserGroup(130)->build(),
        ];

        $url_mapping = new CreatedFileURLMapping();
        $changeset   = NewChangeset::fromFieldsDataArray(
            $this->artifact,
            $this->fields_data,
            $comment_body,
            $comment_format,
            $ugroups_that_are_allowed_to_see,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP,
            $url_mapping,
        );
        self::assertSame($this->artifact, $changeset->getArtifact());
        self::assertSame($this->fields_data, $changeset->getFieldsData());
        self::assertSame($this->submitter, $changeset->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $changeset->getSubmissionTimestamp());
        self::assertSame($url_mapping, $changeset->getUrlMapping());
        $comment = $changeset->getComment();
        self::assertSame($comment_body, $comment->getBody());
        self::assertSame($comment_format, $comment->getFormat());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertSame($ugroups_that_are_allowed_to_see, $comment->getUserGroupsThatAreAllowedToSee());
    }

    public function testItBuildsFromFieldsDataArrayWithEmptyComment(): void
    {
        $changeset = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            $this->artifact,
            $this->fields_data,
            $this->submitter,
            self::SUBMISSION_TIMESTAMP
        );
        self::assertSame($this->artifact, $changeset->getArtifact());
        self::assertSame($this->fields_data, $changeset->getFieldsData());
        self::assertSame($this->submitter, $changeset->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $changeset->getSubmissionTimestamp());
        self::assertTrue($changeset->getUrlMapping()->isEmpty());
        $comment = $changeset->getComment();
        self::assertSame(CommentFormatIdentifier::COMMONMARK, $comment->getFormat());
        self::assertEmpty($comment->getBody());
        self::assertSame($this->submitter, $comment->getSubmitter());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $comment->getSubmissionTimestamp());
        self::assertCount(0, $comment->getUserGroupsThatAreAllowedToSee());
    }
}
