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
        if (! \ForgeConfig::get('enable_creation_git_ref_at_pr_creation')) {
            return;
        }

        $reference_id = $this->dao->createGitReferenceForPullRequest($pull_request->getId());
        while ($this->isReferenceAlreadyTaken($executor_repository_destination, $reference_id)) {
            $reference_id = $this->dao->updateGitReferenceToNextAvailableOne($pull_request->getId());
        }

        $executor_repository_source->push(
            escapeshellarg('gitolite@gl-adm:' . $repository_destination->getPath()) . ' ' .
                escapeshellarg($pull_request->getSha1Src()) . ':' . escapeshellarg(GitPullRequestReference::PR_NAMESPACE . $reference_id . '/head')
        );
    }

    /**
     * @return bool
     */
    private function isReferenceAlreadyTaken(GitExec $executor, $reference_id)
    {
        return count($executor->getReferencesFromPattern(GitPullRequestReference::PR_NAMESPACE . $reference_id)) > 0;
    }
}
