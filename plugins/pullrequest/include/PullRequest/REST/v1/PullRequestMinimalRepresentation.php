<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use GitRepository;
use Tuleap\PullRequest\PullRequest;
use Tuleap\REST\JsonCast;

class PullRequestMinimalRepresentation
{
    const ROUTE = 'pull_requests';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $title;

    /**
     * @var string {@type string}
     */
    public $uri;

    /**
     * @var int {@type GitRepositoryReference}
     */
    public $repository;

    /**
     * @var int {@type GitRepositoryReference}
     */
    public $repository_dest;

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
    public $branch_src;

    /**
     * @var string {@type string}
     */
    public $branch_dest;

    public function buildMinimal(
        PullRequest $pull_request,
        GitRepository $repository,
        GitRepository $repository_dest
    ) {
        $this->id  = JsonCast::toInt($pull_request->getId());

        $project_id  = $repository->getProjectId();
        $purifier    = Codendi_HTMLPurifier::instance();
        $this->title = $purifier->purify($pull_request->getTitle(), CODENDI_PURIFIER_BASIC, $project_id);

        $this->uri = self::ROUTE . '/' . $this->id;

        $this->repository = new GitRepositoryReference();
        $this->repository->build($repository);

        $this->repository_dest = new GitRepositoryReference();
        $this->repository_dest->build($repository_dest);

        $this->user_id       = JsonCast::toInt($pull_request->getUserId());
        $this->creation_date = JsonCast::toDate($pull_request->getCreationDate());
        $this->branch_src    = $pull_request->getBranchSrc();
        $this->branch_dest   = $pull_request->getBranchDest();
    }
}
