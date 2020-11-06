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

namespace Tuleap\Gitlab\REST\v1;

use Git_PermissionsDao;
use Git_SystemEventManager;
use GitDao;
use GitPermissionsManager;
use GitRepositoryFactory;
use GitUserNotAdminException;
use Luracast\Restler\RestException;
use ProjectManager;
use SystemEventManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryDeletor;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryNotInProjectException;
use Tuleap\Gitlab\Repository\GitlabRepositoryNotIntegratedInAnyProjectException;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretDao;
use Tuleap\REST\Header;
use UserManager;

final class GitlabRepositoryResource
{
    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the GitLab repository integration
     */
    public function optionsGitlabRepositories(int $id): void
    {
        Header::allowOptionsDelete();
    }

    /**
     * Delete Gitlab Integrations.
     *
     * /!\ This route is under construction.
     * <br>
     * Delete the given Gitlab integration.
     *
     * @url    DELETE {id}
     * @access protected
     *
     * @param int $id         Id of the GitLab repository integration
     * @param int $project_id Id of the project the GitLab repository integration must be removed. {@from path} {@required true}
     *
     * @status 204
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    protected function deleteGitlabRepository(int $id, int $project_id): void
    {
        $this->optionsGitlabRepositories($id);

        $repository_factory = new GitlabRepositoryFactory(
            new GitlabRepositoryDao()
        );

        $gitlab_repository = $repository_factory->getGitlabRepositoryByIntegrationId($id);

        if ($gitlab_repository === null) {
            throw new RestException(404, "Repository #$id not found.");
        }

        $project = ProjectManager::instance()->getProject($project_id);
        if (! $project || $project->isError()) {
            throw new RestException(404, "Project #$project_id not found.");
        }

        $current_user = UserManager::instance()->getCurrentUser();

        $deletor = new GitlabRepositoryDeletor(
            $this->getGitPermissionsManager(),
            new DBTransactionExecutorWithConnection(
                DBFactory::getMainTuleapDBConnection()
            ),
            new GitlabRepositoryProjectDao(),
            new SecretDao(),
            new GitlabRepositoryDao()
        );

        try {
            $deletor->deleteRepositoryInProject(
                $gitlab_repository,
                $project,
                $current_user
            );
        } catch (GitUserNotAdminException $exception) {
            throw new RestException(401, "User is not Git administrator.");
        } catch (GitlabRepositoryNotInProjectException | GitlabRepositoryNotIntegratedInAnyProjectException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getGitPermissionsManager(): GitPermissionsManager
    {
        $git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            )
        );

        $fine_grained_dao       = new FineGrainedDao();
        $fine_grained_retriever = new FineGrainedRetriever($fine_grained_dao);

        return new GitPermissionsManager(
            new Git_PermissionsDao(),
            $git_system_event_manager,
            $fine_grained_dao,
            $fine_grained_retriever
        );
    }
}
