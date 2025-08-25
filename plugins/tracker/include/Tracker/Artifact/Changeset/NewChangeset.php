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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;

/**
 * I hold all the information needed to create a new Changeset.
 * @psalm-immutable
 */
final class NewChangeset
{
    private function __construct(
        private Artifact $artifact,
        private array $fields_data,
        private \PFUser $submitter,
        private int $submission_timestamp,
        private CreatedFileURLMapping $url_mapping,
        private NewComment $comment,
    ) {
    }

    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    public static function fromFieldsDataArray(
        Artifact $artifact,
        array $fields_data,
        string $comment_body,
        CommentFormatIdentifier $comment_format,
        array $user_groups_that_are_allowed_to_see,
        \PFUser $submitter,
        int $submission_timestamp,
        CreatedFileURLMapping $url_mapping,
    ): self {
        $comment = NewComment::fromParts(
            $comment_body,
            $comment_format,
            $submitter,
            $submission_timestamp,
            $user_groups_that_are_allowed_to_see
        );
        return new self(
            $artifact,
            $fields_data,
            $submitter,
            $submission_timestamp,
            $url_mapping,
            $comment
        );
    }

    public static function fromFieldsDataArrayWithEmptyComment(
        Artifact $artifact,
        array $fields_data,
        \PFUser $submitter,
        int $submission_timestamp,
    ): self {
        $comment = NewComment::buildEmpty($submitter, $submission_timestamp);
        return new self(
            $artifact,
            $fields_data,
            $submitter,
            $submission_timestamp,
            new CreatedFileURLMapping(),
            $comment
        );
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function getFieldsData(): array
    {
        return $this->fields_data;
    }

    public function getSubmitter(): \PFUser
    {
        return $this->submitter;
    }

    public function getSubmissionTimestamp(): int
    {
        return $this->submission_timestamp;
    }

    public function getUrlMapping(): CreatedFileURLMapping
    {
        return $this->url_mapping;
    }

    public function getComment(): NewComment
    {
        return $this->comment;
    }
}
