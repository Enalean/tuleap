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

use Tuleap\PullRequest\PullRequest;
use Tuleap\REST\JsonCast;
use GitRepository;
use Codendi_HTMLPurifier;

class PullRequestRepresentation
{

    const ROUTE          = 'pull_requests';
    const COMMENTS_ROUTE = 'comments';
    const STATUS_ABANDON = 'abandon';
    const STATUS_MERGE   = 'merge';
    const STATUS_REVIEW  = 'review';

    const NO_FASTFORWARD_MERGE = 'no_fastforward';
    const FASTFORWARD_MERGE    = 'fastforward';
    const CONFLICT_MERGE       = 'conflict';
    const UNKNOWN_MERGE        = 'unknown-merge-status';


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
    public $description;

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

    /**
     * @var bool {@type bool}
     */
    public $user_can_merge;

    /**
     * @var bool {@type bool}
     */
    public $user_can_abandon;

    /**
     * @var string {@type string}
     */
    public $merge_status;

    /**
     * @var array {@type PullRequestShortStatRepresentation}
     */
    public $short_stat;


    public function build(
        PullRequest $pull_request,
        GitRepository $repository,
        GitRepository $repository_dest,
        $user_can_merge,
        $user_can_abandon,
        PullRequestShortStatRepresentation $pr_short_stat_representation
    ) {
        $this->id  = JsonCast::toInt($pull_request->getId());

        $project_id        = $repository->getProjectId();
        $purifier          = Codendi_HTMLPurifier::instance();
        $this->title       = $purifier->purify($pull_request->getTitle(), CODENDI_PURIFIER_LIGHT, $project_id);
        $this->description = $purifier->purify($pull_request->getDescription(), CODENDI_PURIFIER_LIGHT, $project_id);

        $this->uri = self::ROUTE . '/' . $this->id;

        $repository_reference = new GitRepositoryReference();
        $repository_reference->build($repository);
        $this->repository = $repository_reference;

        $repository_dest_reference = new GitRepositoryReference();
        $repository_dest_reference->build($repository_dest);
        $this->repository_dest = $repository_dest_reference;

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

        $this->user_can_merge   = $user_can_merge;
        $this->user_can_abandon = $user_can_abandon;
        $this->merge_status     = $this->expandMergeStatusName($pull_request->getMergeStatus());

        $this->short_stat = $pr_short_stat_representation;
    }

    private function expandStatusName($status_acronym)
    {
        $status_name = array(
            PullRequest::STATUS_ABANDONED => self::STATUS_ABANDON,
            PullRequest::STATUS_MERGED    => self::STATUS_MERGE,
            PullRequest::STATUS_REVIEW    => self::STATUS_REVIEW
        );

        return $status_name[$status_acronym];
    }

    private function expandMergeStatusName($merge_status_acronym)
    {
        $status_name = array(
            PullRequest::NO_FASTFORWARD_MERGE => self::NO_FASTFORWARD_MERGE,
            PullRequest::FASTFORWARD_MERGE    => self::FASTFORWARD_MERGE,
            PullRequest::CONFLICT_MERGE       => self::CONFLICT_MERGE,
            PullRequest::UNKNOWN_MERGE        => self::UNKNOWN_MERGE
        );

        return $status_name[$merge_status_acronym];
    }
}
