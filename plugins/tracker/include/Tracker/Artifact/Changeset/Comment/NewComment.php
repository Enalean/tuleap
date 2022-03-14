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

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor;

/**
 * @psalm-immutable
 */
final class NewComment
{
    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    private function __construct(
        private int $changeset_id,
        private string $body,
        private string $format,
        private \PFUser $submitter,
        private int $submission_timestamp,
        private array $user_groups_that_are_allowed_to_see,
    ) {
    }

    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    public static function fromText(
        int $changeset_id,
        string $body,
        \PFUser $submitter,
        int $submission_timestamp,
        array $user_groups_that_are_allowed_to_see,
    ): self {
        return new self(
            $changeset_id,
            $body,
            \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            $submitter,
            $submission_timestamp,
            $user_groups_that_are_allowed_to_see
        );
    }

    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    public static function fromCommonMark(
        int $changeset_id,
        string $body,
        \PFUser $submitter,
        int $submission_timestamp,
        array $user_groups_that_are_allowed_to_see,
    ): self {
        return new self(
            $changeset_id,
            $body,
            \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT,
            $submitter,
            $submission_timestamp,
            $user_groups_that_are_allowed_to_see
        );
    }

    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    public static function fromHTML(
        int $changeset_id,
        string $html_body,
        \PFUser $submitter,
        int $submission_timestamp,
        array $user_groups_that_are_allowed_to_see,
        CreatedFileURLMapping $url_mapping,
    ): self {
        $substitute = new FileURLSubstitutor();
        $new_body   = $substitute->substituteURLsInHTML($html_body, $url_mapping);
        return new self(
            $changeset_id,
            $new_body,
            \Tracker_Artifact_Changeset_Comment::HTML_COMMENT,
            $submitter,
            $submission_timestamp,
            $user_groups_that_are_allowed_to_see
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

    public function getFormat(): string
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
