<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Tests\Builders;

use Tuleap\PullRequest\GitReference\GitPullRequestReference;

final class GitPullRequestReferenceTestBuilder
{
    private int $status = GitPullRequestReference::STATUS_OK;

    private function __construct(private readonly int $git_reference_id)
    {
    }

    public static function aReference(int $reference_id): self
    {
        return new self($reference_id);
    }

    public function thatIsBroken(): self
    {
        $this->status = GitPullRequestReference::STATUS_BROKEN;
        return $this;
    }

    public function thatIsNotYetCreated(): self
    {
        $this->status = GitPullRequestReference::STATUS_NOT_YET_CREATED;
        return $this;
    }

    public function build(): GitPullRequestReference
    {
        return new GitPullRequestReference($this->git_reference_id, $this->status);
    }
}
