<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
namespace Tuleap\Tracker\Test\Builders;

use Tracker_Artifact_Changeset_Comment;

final class ChangesetCommentTestBuilder
{
    private string $body = '';
    private function __construct()
    {
    }

    public static function aComment(): self
    {
        return new self();
    }

    public function withCommentBody(string $comment_body): self
    {
            $this->body = $comment_body;
            return $this;
    }

    public function build(): Tracker_Artifact_Changeset_Comment
    {
        return new Tracker_Artifact_Changeset_Comment(
            10,
            ChangesetTestBuilder::aChangeset(15)->build(),
            0,
            0,
            101,
            1636896700,
            $this->body,
            Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT,
            1,
            null
        );
    }
}
