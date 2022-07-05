<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

use GitDao;
use GitRepository;
use GitRepositoryFactory;
use Tuleap\Git\Permissions\AccessControlVerifier;

final class PaginatedRepositoriesRetriever
{
    public function __construct(
        private GitDao $dao,
        private GitRepositoryFactory $factory,
        private AccessControlVerifier $access_control_verifier,
    ) {
    }

    /**
     * @param int $total_number_repositories
     *
     * @return GitRepository[]
     */
    public function getPaginatedRepositoriesUserCanSee(
        \Project $project,
        \PFUser $user,
        string $scope,
        int $owner_id,
        string $order_by,
        int $limit,
        int $offset,
        &$total_number_repositories,
    ): array {
        $repositories    = [];
        $repository_list = $this->dao->getPaginatedOpenRepositories(
            $project->getID(),
            $scope,
            $owner_id,
            $order_by,
            $limit,
            $offset
        );

        $total_number_repositories = $this->dao->foundRows();
        foreach ($repository_list as $row) {
            $repository = $this->getRepositoryFromRow($row);
            if ($repository && $repository->userCanRead($user)) {
                $repositories[] = $repository;
            }
        }

        return $repositories;
    }

    /**
     * @param int $total_number_repositories
     *
     * @return GitRepository[]
     */
    public function getPaginatedRepositoriesUserCanCreateGivenBranch(
        \Project $project,
        \PFUser $user,
        string $scope,
        int $owner_id,
        string $branch_name,
        string $order_by,
        int $limit,
        int $offset,
        &$total_number_repositories,
    ): array {
        $repositories    = [];
        $repository_list = $this->dao->getPaginatedOpenRepositories(
            $project->getID(),
            $scope,
            $owner_id,
            $order_by,
            $limit,
            $offset
        );

        $total_number_repositories = $this->dao->foundRows();
        foreach ($repository_list as $row) {
            $repository = $this->getRepositoryFromRow($row);
            if ($repository && $this->access_control_verifier->canWrite($user, $repository, $branch_name)) {
                $repositories[] = $repository;
            }
        }

        return $repositories;
    }

    private function getRepositoryFromRow(array $row): ?GitRepository
    {
        if (empty($row)) {
            return null;
        }

        return $this->factory->instanciateFromRow($row);
    }
}
