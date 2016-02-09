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

namespace Tuleap\PullRequest\REST\v1;

use \Tuleap\PullRequest\PullRequest;
use Tuleap\REST\JsonCast;

class PullRequestRepresentation {

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var int {@type int}
     */
    public $repository_id;

    /**
     * @var int {@type int}
     */
    public $user_id;

    /**
     * @var string {@type string}
     */
    public $creation_date;

    /**
     * @var string {@type string}
     */
    public $reference_src;

    /**
     * @var string {@type string}
     */
    public $branch_src;

    /**
     * @var string {@type string}
     */
    public $reference_dest;

    /**
     * @var string {@type string}
     */
    public $branch_dest;

    /**
     * @var string {@type string}
     */
    public $status;

    public function build(PullRequest $pull_request) {
        $this->id             = JsonCast::toInt($pull_request->getId());
        $this->repository_id  = JsonCast::toInt($pull_request->getRepositoryId());
        $this->user_id        = JsonCast::toInt($pull_request->getUserId());
        $this->creation_date  = JsonCast::toDate($pull_request->getCreationDate());
        $this->branch_src     = $pull_request->getBranchSrc();
        $this->reference_src  = $pull_request->getSha1Src();
        $this->branch_dest    = $pull_request->getBranchDest();
        $this->reference_dest = $pull_request->getSha1Dest();
        $this->status         = $pull_request->getStatus();
    }
}