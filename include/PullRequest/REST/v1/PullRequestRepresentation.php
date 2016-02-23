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

use Tuleap\Git\REST\v1\GitRepositoryReference;
use Tuleap\PullRequest\PullRequest;
use Tuleap\REST\JsonCast;
use GitRepository;

class PullRequestRepresentation {

    const ROUTE          = 'pull_requests';
    const COMMENTS_ROUTE = 'comments';
    const STATUS_ABANDON = 'abandon';
    const STATUS_MERGE   = 'merge';
    const STATUS_REVIEW  = 'review';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $uri;

    /**
     * @var int {@type GitRepositoryReference}
     */
    public $repository;

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

    /**
     * @var array {@type array}
     */
    public $resources;


    public function build(PullRequest $pull_request, GitRepository $repository) {
        $this->id  = JsonCast::toInt($pull_request->getId());
        $this->uri = self::ROUTE . '/' . $this->id;

        $repository_reference = new GitRepositoryReference();
        $repository_reference->build($repository);
        $this->repository = $repository_reference;

        $this->user_id        = JsonCast::toInt($pull_request->getUserId());
        $this->creation_date  = JsonCast::toDate($pull_request->getCreationDate());
        $this->branch_src     = $pull_request->getBranchSrc();
        $this->reference_src  = $pull_request->getSha1Src();
        $this->branch_dest    = $pull_request->getBranchDest();
        $this->reference_dest = $pull_request->getSha1Dest();
        $this->status         = $this->expandStatusName($pull_request->getStatus());

        $this->resources = array(
            'comments' => array(
                'uri' => $this->uri . '/'. self::COMMENTS_ROUTE
            )
        );
    }

    private function expandStatusName($status_acronym) {
        $status_name = array(
            PullRequest::STATUS_ABANDONED => self::STATUS_ABANDON,
            PullRequest::STATUS_MERGED    => self::STATUS_MERGE,
            PullRequest::STATUS_REVIEW    => self::STATUS_REVIEW
        );

        return $status_name[$status_acronym];
    }
}
