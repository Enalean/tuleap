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

/**
 * I hold the information needed to create a new Changeset Comment. I go together with a NewChangeset
 * @see \Tuleap\Tracker\Artifact\Changeset\NewChangeset
 * @psalm-immutable
 */
final class NewComment
{
    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    private function __construct(
        private string $body,
        private CommentFormatIdentifier $format,
        private \PFUser $submitter,
        private int $submission_timestamp,
        private array $user_groups_that_are_allowed_to_see,
    ) {
    }

    public static function fromParts(
        string $body,
        CommentFormatIdentifier $format,
        \PFUser $submitter,
        int $submission_timestamp,
        array $user_groups_that_are_allowed_to_see,
    ): self {
        return new self(
            trim($body),
            $format,
            $submitter,
            $submission_timestamp,
            $user_groups_that_are_allowed_to_see
        );
    }

    public static function buildEmpty(\PFUser $submitter, int $submission_timestamp): self
    {
        return new self(
            '',
            CommentFormatIdentifier::COMMONMARK,
            $submitter,
            $submission_timestamp,
            []
        );
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

    /**
     * @return \ProjectUGroup[]
     */
    public function getUserGroupsThatAreAllowedToSee(): array
    {
        return $this->user_groups_that_are_allowed_to_see;
    }
}
