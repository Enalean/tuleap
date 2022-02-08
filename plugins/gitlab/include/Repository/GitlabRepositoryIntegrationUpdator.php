<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use GitPermissionsManager;
use GitUserNotAdminException;
use PFUser;
use Tuleap\DB\DBTransactionExecutor;

class GitlabRepositoryIntegrationUpdator
{
    /**
     * @var GitlabRepositoryIntegrationDao
     */
    private $gitlab_repository_dao;
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var GitlabRepositoryIntegrationFactory
     */
    private $gitlab_repository_factory;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    public function __construct(
        GitlabRepositoryIntegrationDao $gitlab_repository_dao,
        GitPermissionsManager $git_permissions_manager,
        GitlabRepositoryIntegrationFactory $gitlab_repository_factory,
        DBTransactionExecutor $db_transaction_executor,
    ) {
        $this->gitlab_repository_dao     = $gitlab_repository_dao;
        $this->git_permissions_manager   = $git_permissions_manager;
        $this->gitlab_repository_factory = $gitlab_repository_factory;
        $this->db_transaction_executor   = $db_transaction_executor;
    }

    /**
     * @throws GitUserNotAdminException
     * @throws GitlabRepositoryIntegrationNotFoundException
     */
    public function updateTuleapArtifactClosureOfAGitlabIntegration(
        int $gitlab_repository_integration_id,
        bool $allow_artifact_closure,
        PFUser $user,
    ): void {
        $this->db_transaction_executor->execute(
            function () use ($gitlab_repository_integration_id, $allow_artifact_closure, $user) {
                $gitlab_repository = $this->gitlab_repository_factory->getIntegrationById(
                    $gitlab_repository_integration_id
                );
                if (! $gitlab_repository) {
                    throw new GitlabRepositoryIntegrationNotFoundException($gitlab_repository_integration_id);
                }

                $project = $gitlab_repository->getProject();
                if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
                    throw new GitUserNotAdminException();
                }

                $this->gitlab_repository_dao->updateGitlabRepositoryIntegrationAllowArtifactClosureValue(
                    $gitlab_repository_integration_id,
                    $allow_artifact_closure
                );
            }
        );
    }
}
