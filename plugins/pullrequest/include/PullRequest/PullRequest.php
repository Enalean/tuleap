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

    private $id;
    private $title;
    private $description;
    private $repository_id;
    private $user_id;
    private $creation_date;
    private $branch_src;
    private $sha1_src;
    private $repo_dest_id;
    private $branch_dest;
    private $sha1_dest;
    private $status;
    private $merge_status;

    public function __construct(
        $id,
        $title,
        $description,
        $repository_id,
        $user_id,
        $creation_date,
        $branch_src,
        $sha1_src,
        $repo_dest_id,
        $branch_dest,
        $sha1_dest,
        $status = 'R',
        $merge_status = self::UNKNOWN_MERGE
    ) {
        $this->id            = $id;
        $this->title         = $title;
        $this->description   = $description;
        $this->repository_id = $repository_id;
        $this->user_id       = $user_id;
        $this->creation_date = $creation_date;
        $this->branch_src    = $branch_src;
        $this->sha1_src      = $sha1_src;
        $this->repo_dest_id  = $repo_dest_id;
        $this->branch_dest   = $branch_dest;
        $this->sha1_dest     = $sha1_dest;
        $this->status        = $status;
        $this->merge_status  = $merge_status;
    }

    public function createWithNewID(int $new_pull_request_id): self
    {
        $new_pull_request = clone $this;
        $new_pull_request->id = $new_pull_request_id;

        return $new_pull_request;
    }

    public function updateMergeStatus(int $new_merge_status): self
    {
        $updated_pull_request = clone $this;
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

    public function getSha1Src()
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
}
