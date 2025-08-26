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

use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Files\FileURLSubstitutor;

/**
 * I hold all the information needed to store a new comment, including the new changeset ID.
 * I am built from NewComment.
 * @see NewComment
 * @psalm-immutable
 */
final class CommentCreation
{
    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    private function __construct(
        private int $changeset_id,
        private string $body,
        private CommentFormatIdentifier $format,
        private \PFUser $submitter,
        private int $submission_timestamp,
        private array $user_groups_that_are_allowed_to_see,
    ) {
    }

    public static function fromNewComment(
        NewComment $comment,
        int $changeset_id,
        CreatedFileURLMapping $url_mapping,
    ): self {
        $body = $comment->getBody();
        if ($comment->getFormat() === CommentFormatIdentifier::HTML) {
            $substitutor = new FileURLSubstitutor();
            $body        = $substitutor->substituteURLsInHTML($comment->getBody(), $url_mapping);
        }
        return new self(
            $changeset_id,
            $body,
            $comment->getFormat(),
            $comment->getSubmitter(),
            $comment->getSubmissionTimestamp(),
            $comment->getUserGroupsThatAreAllowedToSee()
        );
    }

    public function getChangesetId(): int
    {
        return $this->changeset_id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getFormat(): CommentFormatIdentifier
    {
        return $this->format;
    }

    public function getSubmitter(): \PFUser
    {
        return $this->submitter;
    }

    public function getSubmissionTimestamp(): int
    {
        return $this->submission_timestamp;
    }

    public function getUserGroupsThatAreAllowedToSee(): array
    {
        return $this->user_groups_that_are_allowed_to_see;
    }
}
