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
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;

class GitlabRepositoryCreator implements CreateGitlabRepositories
{
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    /**
     * @var GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;

    /**
     * @var GitlabRepositoryIntegrationDao
     */
    private $gitlab_repository_dao;

    /**
     * @var WebhookCreator
     */
    private $webhook_creator;
    /**
     * @var IntegrationApiTokenInserter
     */
    private $token_inserter;

    public function __construct(
        DBTransactionExecutor $db_transaction_executor,
        GitlabRepositoryIntegrationFactory $repository_integration_factory,
        GitlabRepositoryIntegrationDao $gitlab_repository_dao,
        WebhookCreator $webhook_creator,
        IntegrationApiTokenInserter $token_inserter,
    ) {
        $this->db_transaction_executor        = $db_transaction_executor;
        $this->repository_integration_factory = $repository_integration_factory;
        $this->gitlab_repository_dao          = $gitlab_repository_dao;
        $this->webhook_creator                = $webhook_creator;
        $this->token_inserter                 = $token_inserter;
    }

    /**
     * @throws GitlabRepositoryAlreadyIntegratedInProjectException
     * @throws GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException
     */
    public function integrateGitlabRepositoryInProject(
        Credentials $credentials,
        GitlabProject $gitlab_project,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
    ): GitlabRepositoryIntegration {
        return $this->db_transaction_executor->execute(
            function () use ($credentials, $gitlab_project, $project, $configuration) {
                $gitlab_repository_id  = $gitlab_project->getId();
                $gitlab_web_url        = $gitlab_project->getWebUrl();
                $gitlab_name_with_path = $gitlab_project->getPathWithNamespace();
                $project_id            = (int) $project->getID();

                $already_existing_gitlab_repository = $this->gitlab_repository_dao->isTheGitlabRepositoryAlreadyIntegratedInProject(
                    $project_id,
                    $gitlab_repository_id,
                    $gitlab_web_url
                );

                if ($already_existing_gitlab_repository === true) {
                    throw new GitlabRepositoryAlreadyIntegratedInProjectException(
                        $gitlab_repository_id,
                        $project_id
                    );
                }


                if (
                    $this->gitlab_repository_dao->isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject(
                        $gitlab_name_with_path,
                        $gitlab_web_url,
                        (int) $project->getID()
                    )
                ) {
                    throw new GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException($gitlab_name_with_path);
                }

                return $this->createGitlabRepositoryIntegration(
                    $credentials,
                    $gitlab_project,
                    $project,
                    $configuration
                );
            }
        );
    }

    /**
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    #[\Override]
    public function createGitlabRepositoryIntegration(
        Credentials $credentials,
        GitlabProject $gitlab_project,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
    ): GitlabRepositoryIntegration {
        $gitlab_repository = $this->repository_integration_factory->createRepositoryIntegration(
            $gitlab_project,
            $project,
            $configuration
        );

        $this->webhook_creator->generateWebhookInGitlabProject($credentials, $gitlab_repository);
        $this->token_inserter->insertToken($gitlab_repository, $credentials->getApiToken()->getToken());

        return $gitlab_repository;
    }
}
