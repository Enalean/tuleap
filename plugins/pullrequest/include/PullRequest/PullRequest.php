<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\Label\Labelable;

/**
 * @psalm-immutable
 */
class PullRequest implements Labelable
{
    public const STATUS_ABANDONED = 'A';
    public const STATUS_MERGED    = 'M';
    public const STATUS_REVIEW    = 'R';

    public const UNKNOWN_MERGE        = 0;
    public const NO_FASTFORWARD_MERGE = 1;
    public const FASTFORWARD_MERGE    = 2;
    public const CONFLICT_MERGE       = 3;

    public function __construct(
        private $id,
        private $title,
        private $description,
        private $repository_id,
        private $user_id,
        private $creation_date,
        private $branch_src,
        private string $sha1_src,
        private $repo_dest_id,
        private $branch_dest,
        private $sha1_dest,
        private string $description_format,
        private $status = 'R',
        private $merge_status = self::UNKNOWN_MERGE,
    ) {
    }

    public function createWithNewID(int $new_pull_request_id): self
    {
        $new_pull_request     = clone $this;
        $new_pull_request->id = $new_pull_request_id;

        return $new_pull_request;
    }

    public function updateMergeStatus(int $new_merge_status): self
    {
        $updated_pull_request               = clone $this;
        $updated_pull_request->merge_status = $new_merge_status;

        return $updated_pull_request;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getRepositoryId()
    {
        return $this->repository_id;
    }

    public function getBranchSrc()
    {
        return $this->branch_src;
    }

    public function getSha1Src(): string
    {
        return $this->sha1_src;
    }

    public function getRepoDestId()
    {
        return $this->repo_dest_id;
    }

    public function getBranchDest()
    {
        return $this->branch_dest;
    }

    public function getSha1Dest()
    {
        return $this->sha1_dest;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMergeStatus()
    {
        return $this->merge_status;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getCreationDate()
    {
        return $this->creation_date;
    }

    public function getDescriptionFormat(): string
    {
        return $this->description_format;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['title'],
            $row['description'],
            $row['repository_id'],
            $row['user_id'],
            $row['creation_date'],
            $row['branch_src'],
            $row['sha1_src'],
            $row['repo_dest_id'],
            $row['branch_dest'],
            $row['sha1_dest'],
            $row["description_format"],
            $row['status'],
            $row['merge_status'],
        );
    }
}
