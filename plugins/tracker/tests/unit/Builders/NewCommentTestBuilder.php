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

namespace Tuleap\Tracker\Test\Builders;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;

final class NewCommentTestBuilder
{
    private CommentFormatIdentifier $format;
    private \PFUser $submitter;
    private int $submission_timestamp         = 1551390656; // 2019-02-28T22:50:56
    private array $user_groups_allowed_to_see = [];

    private function __construct(private string $body)
    {
        $this->submitter = UserTestBuilder::buildWithDefaults();
        $this->format    = CommentFormatIdentifier::buildCommonMark();
    }

    public static function aNewComment(string $body): self
    {
        return new self($body);
    }

    public function withSubmitter(\PFUser $submitter): self
    {
        $this->submitter = $submitter;
        return $this;
    }

    public function build(): NewComment
    {
        return NewComment::fromParts(
            $this->body,
            $this->format,
            $this->submitter,
            $this->submission_timestamp,
            $this->user_groups_allowed_to_see
        );
    }
}
