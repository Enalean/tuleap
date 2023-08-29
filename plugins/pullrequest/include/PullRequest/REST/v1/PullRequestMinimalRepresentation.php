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

namespace Tuleap\PullRequest\REST\v1;

use Codendi_HTMLPurifier;
use GitRepository;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\PullRequest\PullRequest;
use Tuleap\REST\JsonCast;

class PullRequestMinimalRepresentation
{
    public const ROUTE = 'pull_requests';

    /**
     * @var GitoliteAccessURLGenerator
     */
    private $gitolite_access_URL_generator;

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
     * @var GitRepositoryReference {@type GitRepositoryReference}
     */
    public $repository;

    /**
     * @var GitRepositoryReference {@type GitRepositoryReference}
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
    /**
     * @var string {@type string}
     */
    public $status;
    /**
     * @var PullRequestHEADRepresentation {@type PullRequestHEADRepresentation}
     */
    public $head;

    public function __construct(GitoliteAccessURLGenerator $gitolite_access_URL_generator)
    {
        $this->gitolite_access_URL_generator = $gitolite_access_URL_generator;
    }

    public function buildMinimal(
        PullRequest $pull_request,
        GitRepository $repository,
        GitRepository $repository_dest,
    ) {
        $this->id = JsonCast::toInt($pull_request->getId());

        $project_id  = $repository->getProjectId();
        $purifier    = Codendi_HTMLPurifier::instance();
        $this->title = $purifier->purify($pull_request->getTitle(), Codendi_HTMLPurifier::CONFIG_BASIC, $project_id);

        $this->uri = self::ROUTE . '/' . $this->id;

        $this->repository = new GitRepositoryReference($this->gitolite_access_URL_generator);
        $this->repository->build($repository);

        $this->repository_dest = new GitRepositoryReference($this->gitolite_access_URL_generator);
        $this->repository_dest->build($repository_dest);

        $this->user_id       = JsonCast::toInt($pull_request->getUserId());
        $this->creation_date = JsonCast::toDate($pull_request->getCreationDate());
        $this->branch_src    = $pull_request->getBranchSrc();
        $this->branch_dest   = $pull_request->getBranchDest();
        $this->status        = $this->expandStatusName($pull_request->getStatus());
        $this->head          = new PullRequestHEADRepresentation($pull_request);
    }

    private function expandStatusName($status_acronym)
    {
        $status_name = [
            PullRequest::STATUS_ABANDONED => PullRequestRepresentation::STATUS_ABANDON,
            PullRequest::STATUS_MERGED    => PullRequestRepresentation::STATUS_MERGE,
            PullRequest::STATUS_REVIEW    => PullRequestRepresentation::STATUS_REVIEW,
        ];

        return $status_name[$status_acronym];
    }
}
