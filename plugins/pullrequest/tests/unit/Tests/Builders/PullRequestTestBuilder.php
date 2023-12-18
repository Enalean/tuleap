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
    private int $id                         = 15;
    private string $title                   = "This is a title";
    private string $description             = "This is a description";
    private int $repository_id              = 5;
    private int $user_id                    = 105;
    private int $creation_timestamp         = 1679910276;
    private string $source_branch_name      = "pr-1";
    private string $destination_branch_name = "master";
    private string $source_sha1             = "1b8e9594dc204eb9907f78df8bb60f564c389832";
    private string $destination_sha1        = "aba2416a22a0c5d985207fbed10de5d1c8c91397";
    private int $repo_dest_id               = 5;
    private int $merge_status               = PullRequest::FASTFORWARD_MERGE;
    private string $description_format      = TimelineComment::FORMAT_TEXT;

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

    public function withRepositoryDestinationId(int $repo_dest_id): self
    {
        $this->repo_dest_id = $repo_dest_id;
        return $this;
    }

    public function createdBy(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function createdAt(int $creation_timestamp): self
    {
        $this->creation_timestamp = $creation_timestamp;
        return $this;
    }

    public function fromSourceBranch(string $source_branch_name): self
    {
        $this->source_branch_name = $source_branch_name;
        return $this;
    }

    public function toDestinationBranch(string $destination_branch_name): self
    {
        $this->destination_branch_name = $destination_branch_name;
        return $this;
    }

    public function fromSourceGitSHA1(string $source_sha1): self
    {
        $this->source_sha1 = $source_sha1;
        return $this;
    }

    public function toDestinationGitSHA1(string $destination_sha1): self
    {
        $this->destination_sha1 = $destination_sha1;
        return $this;
    }

    /**
     * @psalm-param PullRequest::UNKNOWN_MERGE|PullRequest::NO_FASTFORWARD_MERGE|PullRequest::FASTFORWARD_MERGE|PullRequest::CONFLICT_MERGE $merge_status
     */
    public function withMergeStatus(int $merge_status): self
    {
        $this->merge_status = $merge_status;
        return $this;
    }

    /**
     * @psalm-param TimelineComment::FORMAT_TEXT|TimelineComment::FORMAT_MARKDOWN $format
     */
    public function withDescription(string $format, string $description): self
    {
        $this->description        = $description;
        $this->description_format = $format;
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
            $this->creation_timestamp,
            $this->source_branch_name,
            $this->source_sha1,
            $this->repo_dest_id,
            $this->destination_branch_name,
            $this->destination_sha1,
            $this->description_format,
            $this->status,
            $this->merge_status
        );
    }
}
