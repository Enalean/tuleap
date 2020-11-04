<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use GitPermissionsManager;
use GitUserNotAdminException;
use PFUser;
use Project;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretDao;

class GitlabRepositoryDeletor
{
    /**
     * @var GitlabRepositoryProjectDao
     */
    private $gitlab_repository_project_dao;

    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var SecretDao
     */
    private $secret_dao;
    /**
     * @var GitlabRepositoryDao
     */
    private $gitlab_repository_dao;

    public function __construct(
        GitPermissionsManager $git_permissions_manager,
        DBTransactionExecutor $db_transaction_executor,
        GitlabRepositoryProjectDao $gitlab_repository_project_dao,
        SecretDao $secret_dao,
        GitlabRepositoryDao $gitlab_repository_dao
    ) {
        $this->git_permissions_manager       = $git_permissions_manager;
        $this->db_transaction_executor       = $db_transaction_executor;
        $this->gitlab_repository_project_dao = $gitlab_repository_project_dao;
        $this->secret_dao                    = $secret_dao;
        $this->gitlab_repository_dao         = $gitlab_repository_dao;
    }

    /**
     * @throws GitUserNotAdminException
     * @throws GitlabRepositoryNotInProjectException
     * @throws GitlabRepositoryNotIntegratedInAnyProjectException
     */
    public function deleteRepositoryInProject(GitlabRepository $gitlab_repository, Project $project, PFUser $user): void
    {
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new GitUserNotAdminException();
        }

        $this->db_transaction_executor->execute(function () use ($gitlab_repository, $project) {
            $gitlab_repository_id = $gitlab_repository->getId();
            $project_id           = (int) $project->getID();

            $project_ids = $this->gitlab_repository_project_dao->searchProjectsTheGitlabRepositoryIsIntegratedIn(
                $gitlab_repository_id
            );

            if (count($project_ids) === 0) {
                throw new GitlabRepositoryNotIntegratedInAnyProjectException($gitlab_repository_id);
            }

            if (! in_array($project_id, $project_ids, true)) {
                throw new GitlabRepositoryNotInProjectException($gitlab_repository_id, $project_id);
            }

            if (count($project_ids) > 1) {
                $this->gitlab_repository_project_dao->removeGitlabRepositoryIntegrationInProject(
                    $gitlab_repository_id,
                    $project_id
                );
            } else {
                $this->secret_dao->deleteGitlabRepositoryWebhookSecret($gitlab_repository_id);
                $this->gitlab_repository_project_dao->removeGitlabRepositoryIntegrationInProject(
                    $gitlab_repository_id,
                    $project_id
                );
                $this->gitlab_repository_dao->deleteGitlabRepository($gitlab_repository_id);
            }
        });
    }
}
