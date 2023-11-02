<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

class GitPullRequestReferenceUpdater implements UpdateGitPullRequestReference
{
    /**
     * @var GitPullRequestReferenceDAO
     */
    private $dao;
    /**
     * @var GitPullRequestReferenceNamespaceAvailabilityChecker
     */
    private $namespace_availability_checker;

    public function __construct(
        GitPullRequestReferenceDAO $dao,
        GitPullRequestReferenceNamespaceAvailabilityChecker $namespace_availability_checker,
    ) {
        $this->dao                            = $dao;
        $this->namespace_availability_checker = $namespace_availability_checker;
    }

    /**
     * @throws \Git_Command_Exception
     * @throws GitReferenceNotFound
     */
    public function updatePullRequestReference(
        PullRequest $pull_request,
        GitExec $executor_repository_source,
        GitExec $executor_repository_destination,
        GitRepository $repository_destination,
    ): void {
        if ((int) $pull_request->getRepoDestId() !== (int) $repository_destination->getId()) {
            throw new \LogicException('Destination repository ID does not match the one of the PR');
        }

        $reference_row = $this->dao->getReferenceByPullRequestId($pull_request->getId());
        if (empty($reference_row)) {
            throw new GitReferenceNotFound($pull_request);
        }
        $reference = new GitPullRequestReference($reference_row['reference_id'], $reference_row['status']);
        if (! $reference->isGitReferenceUpdatable()) {
            return;
        }

        try {
            if ($reference->isGitReferenceNeedToBeCreatedInRepository()) {
                $reference = $this->ensureAvailabilityGitReferenceNamespace(
                    $pull_request,
                    $executor_repository_destination,
                    $reference
                );
            }
            $executor_repository_source->pushForce(
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
     * @return GitPullRequestReference
     */
    private function ensureAvailabilityGitReferenceNamespace(
        PullRequest $pull_request,
        GitExec $executor_repository,
        GitPullRequestReference $reference,
    ) {
        $reference_id = $reference->getGitReferenceId();
        while (! $this->namespace_availability_checker->isAvailable($executor_repository, $reference_id)) {
            $reference_id = $this->dao->updateGitReferenceToNextAvailableOne($pull_request->getId());
        }
        return GitPullRequestReference::buildReferenceWithUpdatedId($reference_id, $reference);
    }
}
