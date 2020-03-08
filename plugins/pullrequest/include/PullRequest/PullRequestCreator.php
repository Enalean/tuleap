<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use EventManager;
use GitRepository;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestTargetException;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceCreator;

class PullRequestCreator
{

    /**
     * @var Factory
     */
    private $pull_request_factory;

    /**
     * @var Dao
     */
    private $pull_request_dao;
    /**
     * @var PullRequestMerger
     */
    private $pull_request_merger;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var GitPullRequestReferenceCreator
     */
    private $git_pull_request_reference_creator;


    public function __construct(
        Factory $pull_request_factory,
        Dao $pull_request_dao,
        PullRequestMerger $pull_request_merger,
        EventManager $event_manager,
        GitPullRequestReferenceCreator $git_pull_request_reference_creator
    ) {
        $this->pull_request_factory               = $pull_request_factory;
        $this->pull_request_dao                   = $pull_request_dao;
        $this->pull_request_merger                = $pull_request_merger;
        $this->event_manager                      = $event_manager;
        $this->git_pull_request_reference_creator = $git_pull_request_reference_creator;
    }

    public function generatePullRequest(
        GitRepository $repository_src,
        $branch_src,
        GitRepository $repository_dest,
        $branch_dest,
        \PFUser $creator
    ) {
        if (! $repository_src || ! $repository_dest) {
            return false;
        }

        if ($creator->isAnonymous()) {
            throw new PullRequestAnonymousUserException();
        }

        if ($repository_src->getId() != $repository_dest->getId() && $repository_src->getParentId() != $repository_dest->getId()) {
            throw new PullRequestTargetException();
        }

        if ($repository_dest->isMigratedToGerrit()) {
            throw new PullRequestRepositoryMigratedOnGerritException();
        }

        $executor_repository_source      = new GitExec($repository_src->getFullPath(), $repository_src->getFullPath());
        $executor_repository_destination = GitExec::buildFromRepository($repository_dest);
        $sha1_src                        = $executor_repository_source->getBranchSha1("refs/heads/$branch_src");
        $sha1_dest                       = $executor_repository_destination->getBranchSha1("refs/heads/$branch_dest");
        $repo_dest_id                    = $repository_dest->getId();
        $repo_src_id                     = $repository_src->getId();

        if ($sha1_src === $sha1_dest) {
            throw new PullRequestCannotBeCreatedException();
        }

        $this->checkIfPullRequestAlreadyExists($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest);

        $commit_message = $executor_repository_source->getCommitMessage($sha1_src);
        $first_line     = array_shift($commit_message);
        $other_lines    = implode("\n", $commit_message);

        $pull_request = new PullRequest(
            0,
            $first_line,
            $other_lines,
            $repo_src_id,
            $creator->getId(),
            time(),
            $branch_src,
            $sha1_src,
            $repo_dest_id,
            $branch_dest,
            $sha1_dest
        );
        $pull_request = $this->pull_request_factory->create($creator, $pull_request, $repository_src->getProjectId());

        $this->git_pull_request_reference_creator->createPullRequestReference(
            $pull_request,
            $executor_repository_source,
            $executor_repository_destination,
            $repository_dest
        );

        $merge_status = $this->pull_request_merger->detectMergeabilityStatus(
            $executor_repository_destination,
            $pull_request->getSha1Src(),
            $pull_request->getSha1Dest()
        );
        $pull_request = $pull_request->updateMergeStatus($merge_status);
        $this->pull_request_factory->updateMergeStatus($pull_request, $merge_status);

        $event = new GetCreatePullRequest($pull_request, $creator, $repository_dest->getProject());
        $this->event_manager->processEvent($event);

        return $pull_request;
    }

    private function checkIfPullRequestAlreadyExists($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest)
    {
        $row = $this->pull_request_dao->searchByReferences($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest);

        if ($row) {
            throw new PullRequestAlreadyExistsException();
        }
    }
}
