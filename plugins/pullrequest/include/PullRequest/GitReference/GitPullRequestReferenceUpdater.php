<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

use GitRepository;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceUpdater
{
    /**
     * @var GitPullRequestReferenceDAO
     */
    private $dao;
    /**
     * @var GitPullRequestReferenceCreator
     */
    private $reference_creator;

    public function __construct(GitPullRequestReferenceDAO $dao, GitPullRequestReferenceCreator $reference_creator)
    {
        $this->dao               = $dao;
        $this->reference_creator = $reference_creator;
    }

    public function updatePullRequestReference(
        PullRequest $pull_request,
        GitExec $executor_repository_source,
        GitExec $executor_repository_destination,
        GitRepository $repository_destination
    ) {
        if ((int) $pull_request->getRepoDestId() !== (int) $repository_destination->getId()) {
            throw new \LogicException('Destination repository ID does not match the one of the PR');
        }

        $reference_row = $this->dao->getReferenceByPullRequestId($pull_request->getId());
        if (empty($reference_row)) {
            $this->reference_creator->createPullRequestReference(
                $pull_request,
                $executor_repository_source,
                $executor_repository_destination,
                $repository_destination
            );
            return;
        }

        $executor_repository_source->push(
            '--force ' . escapeshellarg('gitolite@gl-adm:' . $repository_destination->getPath()) . ' ' .
            escapeshellarg($pull_request->getSha1Src()) . ':' . escapeshellarg(GitPullRequestReference::PR_NAMESPACE . $reference_row['reference_id'] . '/head')
        );
    }
}
