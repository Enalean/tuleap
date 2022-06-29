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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;

final class CreateCommentOnlyChangesetStub implements \Tuleap\Tracker\Artifact\Changeset\CreateCommentOnlyChangeset
{
    private ?NewComment $new_comment = null;
    private ?Artifact $artifact      = null;

    private function __construct(
        private \Tracker_Artifact_Changeset|Fault $return_value,
    ) {
    }

    public static function withChangeset(\Tracker_Artifact_Changeset $changeset): self
    {
        return new self($changeset);
    }

    public static function withFault(Fault $fault): self
    {
        return new self($fault);
    }

    public function getNewComment(): ?NewComment
    {
        return $this->new_comment;
    }

    public function getArtifact(): ?Artifact
    {
        return $this->artifact;
    }

    public function createCommentOnlyChangeset(NewComment $new_comment, Artifact $artifact): Ok|Err
    {
        $this->new_comment = $new_comment;
        $this->artifact    = $artifact;

        if ($this->return_value instanceof Fault) {
            return Result::err($this->return_value);
        }
        return Result::ok($this->return_value);
    }
}
