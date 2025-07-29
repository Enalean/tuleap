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

namespace Tuleap\Tracker\Test\Builders;

use Tracker_Artifact_Changeset_Comment;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;

final class ChangesetTestBuilder
{
    private Artifact $artifact;
    private int $submitted_by_id        = 101;
    private int $submission_timestamp   = 1234567890;
    private ?string $submitted_by_email = 'anonymous_user@example.com';
    private ?string $text_comment       = null;

    private function __construct(private int $id)
    {
        $artifact_id    = 171;
        $this->artifact = ArtifactTestBuilder::anArtifact($artifact_id)->build();
    }

    public static function aChangeset(int $changeset_id): self
    {
        return new self($changeset_id);
    }

    public function ofArtifact(Artifact $artifact): self
    {
        $this->artifact = $artifact;
        return $this;
    }

    public function submittedBy(int $user_id): self
    {
        $this->submitted_by_id    = $user_id;
        $this->submitted_by_email = '';

        return $this;
    }

    public function submittedByAnonymous(string $email): self
    {
        $this->submitted_by_id    = \PFUser::ANONYMOUS_USER_ID;
        $this->submitted_by_email = $email;

        return $this;
    }

    /**
     * @param int $submission_timestamp UNIX Timestamp
     */
    public function submittedOn(int $submission_timestamp): self
    {
        $this->submission_timestamp = $submission_timestamp;
        return $this;
    }

    public function withTextComment(string $comment): self
    {
        $this->text_comment = $comment;
        return $this;
    }

    public function build(): \Tracker_Artifact_Changeset
    {
        $changeset = new \Tracker_Artifact_Changeset(
            $this->id,
            $this->artifact,
            $this->submitted_by_id,
            $this->submission_timestamp,
            $this->submitted_by_email
        );

        if ($this->text_comment !== null) {
            $comment = new Tracker_Artifact_Changeset_Comment(
                1,
                $changeset,
                0,
                0,
                (int) $changeset->getSubmittedBy(),
                (int) $changeset->getSubmittedOn(),
                $this->text_comment,
                CommentFormatIdentifier::TEXT->value,
                -1,
                null
            );
            $changeset->setLatestComment($comment);
        }

        return $changeset;
    }
}
