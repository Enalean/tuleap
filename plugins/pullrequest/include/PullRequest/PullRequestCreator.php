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

namespace Tuleap\PullRequest;

use GitRepositoryFactory;
use GitRepository;
use UserManager;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;

class PullRequestCreator {

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var Factory
     */
    private $pull_request_factory;

    /**
     * @var Dao
     */
    private $pull_request_dao;

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    public function __construct(
        Factory $pull_request_factory,
        Dao $pull_request_dao,
        GitRepositoryFactory $git_repository_factory,
        UserManager $user_manager
    ) {
        $this->pull_request_factory   = $pull_request_factory;
        $this->pull_request_dao       = $pull_request_dao;
        $this->git_repository_factory = $git_repository_factory;
        $this->user_manager           = $user_manager;
    }

    public function generatePullRequest(GitRepository $git_repository, $branch_src, $branch_dest) {
        $repository = $this->git_repository_factory->getRepositoryById($git_repository->getId());
        $user       = $this->user_manager->getCurrentUser();

        if ($repository) {
            $executor  = new GitExec($repository->getFullPath(), $repository->getFullPath());
            $sha1_src  = $executor->getReferenceBranch($branch_src);
            $sha1_dest = $executor->getReferenceBranch($branch_dest);

            if ($sha1_src === $sha1_dest) {
                throw new PullRequestCannotBeCreatedException();
            }

            $this->checkIfPullRequestAlreadyExists($sha1_src, $sha1_dest);

            return $this->pull_request_factory->create(
                $repository,
                $user,
                $branch_src,
                $sha1_src,
                $branch_dest,
                $sha1_dest
            );
        }

        return false;
    }

    private function checkIfPullRequestAlreadyExists($sha1_src, $sha1_dest) {
        $row = $this->pull_request_dao->searchByShaOnes($sha1_src, $sha1_dest)->getRow();

        if ($row) {
            throw new PullRequestAlreadyExistsException();
        }
    }

}
