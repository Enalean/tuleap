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

class GitPullRequestReferenceCreator
{
    /**
     * @var GitPullRequestReferenceDAO
     */
    private $dao;

    public function __construct(GitPullRequestReferenceDAO $dao)
    {
        $this->dao = $dao;
    }

    public function createPullRequestReference(
        PullRequest $pull_request,
        GitExec $executor_repository_source,
        GitExec $executor_repository_destination,
        GitRepository $repository_destination
    ) {
        if ((int) $pull_request->getRepoDestId() !== (int) $repository_destination->getId()) {
            throw new \LogicException('Destination repository ID does not match the one of the PR');
        }

        $reference_id = $this->dao->createGitReferenceForPullRequest(
            $pull_request->getId(),
            GitPullRequestReference::STATUS_NOT_YET_CREATED
        );
        while ($this->isReferenceAlreadyTaken($executor_repository_destination, $reference_id)) {
            $reference_id = $this->dao->updateGitReferenceToNextAvailableOne($pull_request->getId());
        }

        $reference = new GitPullRequestReference($reference_id, GitPullRequestReference::STATUS_NOT_YET_CREATED);
        try {
            $executor_repository_source->push(
                escapeshellarg('gitolite@gl-adm:' . $repository_destination->getPath()) . ' ' .
                escapeshellarg($pull_request->getSha1Src()) . ':' . escapeshellarg($reference->getGitHeadReference())
            );
        } catch (\Git_Command_Exception $ex) {
            $this->dao->updateStatusByPullRequestId($pull_request->getId(), GitPullRequestReference::STATUS_BROKEN);
            throw $ex;
        }
        $this->dao->updateStatusByPullRequestId($pull_request->getId(), GitPullRequestReference::STATUS_OK);
    }

    /**
     * @return bool
     */
    private function isReferenceAlreadyTaken(GitExec $executor, $reference_id)
    {
        return count($executor->getReferencesFromPattern(GitPullRequestReference::PR_NAMESPACE . $reference_id)) > 0;
    }
}
