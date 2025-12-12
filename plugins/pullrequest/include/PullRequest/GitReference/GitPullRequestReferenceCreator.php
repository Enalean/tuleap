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
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Process\ProcessFactory;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceCreator
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
        private readonly ProcessFactory $process_factory,
    ) {
        $this->dao                            = $dao;
        $this->namespace_availability_checker = $namespace_availability_checker;
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function createPullRequestReference(
        PullRequest $pull_request,
        GitRepository $repository_source,
        GitExec $executor_repository_destination,
        GitRepository $repository_destination,
    ): Ok|Err {
        if ((int) $pull_request->getRepoDestId() !== (int) $repository_destination->getId()) {
            throw new \LogicException('Destination repository ID does not match the one of the PR');
        }

        $reference_id = $this->dao->createGitReferenceForPullRequest(
            $pull_request->getId(),
            GitPullRequestReference::STATUS_NOT_YET_CREATED
        );
        while (! $this->namespace_availability_checker->isAvailable($executor_repository_destination, $reference_id)) {
            $reference_id = $this->dao->updateGitReferenceToNextAvailableOne($pull_request->getId());
        }

        $reference      = new GitPullRequestReference($reference_id, GitPullRequestReference::STATUS_NOT_YET_CREATED);
        $create_process = $this->process_factory->buildProcess([
            'sudo',
            '-u',
            'gitolite',
            'DISPLAY_ERRORS=true',
            __DIR__ . '/../../../bin/create-pr-reference.php',
            $repository_source->getFullPath(),
            $repository_destination->getFullPath(),
            $pull_request->getSha1Src(),
            $reference->getGitHeadReference(),
        ]);

        return $create_process->run()->match(
            function () use ($pull_request): Ok {
                $this->dao->updateStatusByPullRequestId($pull_request->getId(), GitPullRequestReference::STATUS_OK);
                return Result::ok(null);
            },
            function (Fault $fault) use ($pull_request): Err {
                $this->dao->updateStatusByPullRequestId($pull_request->getId(), GitPullRequestReference::STATUS_BROKEN);
                return Result::err($fault);
            }
        );
    }
}
