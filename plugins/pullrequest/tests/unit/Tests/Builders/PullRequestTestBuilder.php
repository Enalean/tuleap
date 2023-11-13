<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Tests\Builders;

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;

final class PullRequestTestBuilder
{
    private int $id                    = 15;
    private string $title              = "This is a title";
    private string $description        = "This is a description";
    private int $repository_id         = 5;
    private int $user_id               = 105;
    private int $creation_date         = 1679910276;
    private string $branch_src         = "pr-1";
    private string $branch_dest        = "master";
    private string $sha1_src           = "1b8e9594dc204eb9907f78df8bb60f564c389832";
    private string $sha1_dest          = "aba2416a22a0c5d985207fbed10de5d1c8c91397";
    private int $repo_dest_id          = 5;
    private int $merge_status          = PullRequest::FASTFORWARD_MERGE;
    private string $description_format = TimelineComment::FORMAT_TEXT;


    private function __construct(
        private string $status,
    ) {
    }

    public static function aPullRequestInReview(): self
    {
        return new self(PullRequest::STATUS_REVIEW);
    }

    public static function anAbandonedPullRequest(): self
    {
        return new self(PullRequest::STATUS_ABANDONED);
    }

    public static function aMergedPullRequest(): self
    {
        return new self(PullRequest::STATUS_MERGED);
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withRepositoryId(int $repository_id): self
    {
        $this->repository_id = $repository_id;
        return $this;
    }

    public function build(): PullRequest
    {
        return new PullRequest(
            $this->id,
            $this->title,
            $this->description,
            $this->repository_id,
            $this->user_id,
            $this->creation_date,
            $this->branch_src,
            $this->sha1_src,
            $this->repo_dest_id,
            $this->branch_dest,
            $this->sha1_dest,
            $this->description_format,
            $this->status,
            $this->merge_status
        );
    }
}
