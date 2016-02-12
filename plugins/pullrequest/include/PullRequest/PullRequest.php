<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class PullRequest {

    private $id;
    private $repository_id;
    private $user_id;
    private $creation_date;
    private $branch_src;
    private $sha1_src;
    private $branch_dest;
    private $sha1_dest;
    private $status;

    public function __construct(
        $id,
        $repository_id,
        $user_id,
        $creation_date,
        $branch_src,
        $sha1_src,
        $branch_dest,
        $sha1_dest,
        $status = 'R'
    ) {
        $this->id            = $id;
        $this->repository_id = $repository_id;
        $this->user_id       = $user_id;
        $this->creation_date = $creation_date;
        $this->branch_src    = $branch_src;
        $this->sha1_src      = $sha1_src;
        $this->branch_dest   = $branch_dest;
        $this->sha1_dest     = $sha1_dest;
        $this->status        = $status;
    }

    public function getId() {
        return $this->id;
    }

    public function getRepositoryId() {
        return $this->repository_id;
    }

    public function getBranchSrc() {
        return $this->branch_src;
    }

    public function getSha1Src() {
        return $this->sha1_src;
    }

    public function getBranchDest() {
        return $this->branch_dest;
    }

    public function getSha1Dest() {
        return $this->sha1_dest;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getCreationDate() {
        return $this->creation_date;
    }
}