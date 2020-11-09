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

use Project;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\API\Credentials;

class GitlabRepositoryCreator
{
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    /**
     * @var GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    /**
     * @var GitlabRepositoryProjectDao
     */
    private $gitlab_repository_project_dao;

    /**
     * @var GitlabRepositoryDao
     */
    private $gitlab_repository_dao;

    /**
     * @var WebhookCreator
     */
    private $webhook_creator;

    public function __construct(
        DBTransactionExecutor $db_transaction_executor,
        GitlabRepositoryFactory $gitlab_repository_factory,
        GitlabRepositoryDao $gitlab_repository_dao,
        GitlabRepositoryProjectDao $gitlab_repository_project_dao,
        WebhookCreator $webhook_creator
    ) {
        $this->db_transaction_executor       = $db_transaction_executor;
        $this->gitlab_repository_factory     = $gitlab_repository_factory;
        $this->gitlab_repository_dao         = $gitlab_repository_dao;
        $this->gitlab_repository_project_dao = $gitlab_repository_project_dao;
        $this->webhook_creator               = $webhook_creator;
    }

    /**
     * @throws GitlabRepositoryAlreadyIntegratedInProjectException
     */
    public function integrateGitlabRepositoryInProject(
        Credentials $credentials,
        GitlabProject $gitlab_project,
        Project $project
    ): GitlabRepository {
        return $this->db_transaction_executor->execute(function () use ($credentials, $gitlab_project, $project) {
            $gitlab_internal_id = $gitlab_project->getId();
            $gitlab_web_url     = $gitlab_project->getWebUrl();

            $already_existing_gitlab_repository = $this->gitlab_repository_factory->getGitlabRepositoryByInternalIdAndPath(
                $gitlab_internal_id,
                $gitlab_web_url
            );
            if ($already_existing_gitlab_repository !== null) {
                $this->addAlreadyIntegratedGitlabRepositoryInProject(
                    $already_existing_gitlab_repository,
                    $project
                );

                return $already_existing_gitlab_repository;
            }

            return $this->createGitlabRepositoryIntegration(
                $credentials,
                $gitlab_project,
                $project
            );
        });
    }

    /**
     * @throws GitlabRepositoryAlreadyIntegratedInProjectException
     */
    private function addAlreadyIntegratedGitlabRepositoryInProject(
        GitlabRepository $already_existing_gitlab_repository,
        Project $project
    ): void {
        $gitlab_repository_id = $already_existing_gitlab_repository->getId();
        $project_id           = (int) $project->getID();


        if (
            $this->gitlab_repository_project_dao->isGitlabRepositoryIntegratedInProject(
                $gitlab_repository_id,
                $project_id
            )
        ) {
            throw new GitlabRepositoryAlreadyIntegratedInProjectException(
                $gitlab_repository_id,
                $project_id
            );
        }

        $this->gitlab_repository_project_dao->addGitlabRepositoryIntegrationInProject(
            $gitlab_repository_id,
            $project_id
        );
    }

    private function createGitlabRepositoryIntegration(
        Credentials $credentials,
        GitlabProject $gitlab_project,
        Project $project
    ): GitlabRepository {
        $internal_gitlab_id = $gitlab_project->getId();

        $gitlab_repository_id = $this->gitlab_repository_dao->createGitlabRepository(
            $internal_gitlab_id,
            $gitlab_project->getName(),
            $gitlab_project->getPathWithNamespace(),
            $gitlab_project->getDescription(),
            $gitlab_project->getWebUrl(),
            $gitlab_project->getLastActivityAt()->getTimestamp(),
        );

        $gitlab_repository = $this->gitlab_repository_factory->getGitlabRepositoryByGitlabProjectAndIntegrationId(
            $gitlab_project,
            $gitlab_repository_id
        );

        $this->gitlab_repository_project_dao->addGitlabRepositoryIntegrationInProject(
            $gitlab_repository_id,
            (int) $project->getID()
        );

        $this->webhook_creator->addWebhookInGitlabProject($credentials, $gitlab_repository);

        return $gitlab_repository;
    }
}
