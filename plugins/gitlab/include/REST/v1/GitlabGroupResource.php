<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use BackendLogger;
use Git_PermissionsDao;
use Git_SystemEventManager;
use GitDao;
use gitlabPlugin;
use GitPermissionsManager;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use SystemEventManager;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\Group\GitlabGroupInformationRetriever;
use Tuleap\Gitlab\Group\GitlabGroupDAO;
use Tuleap\Gitlab\Group\GitlabGroupFactory;
use Tuleap\Gitlab\Group\GroupCreator;
use Tuleap\Gitlab\Group\Token\GroupApiToken;
use Tuleap\Gitlab\Group\Token\GroupApiTokenDAO;
use Tuleap\Gitlab\Group\Token\GroupTokenInserter;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreator;
use Tuleap\Gitlab\Repository\GitlabRepositoryGroupLinkHandler;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\REST\Header;
use UserManager;

final class GitlabGroupResource
{
    public const ROUTE = 'gitlab_groups';

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Link a Gitlab group with Tuleap.
     *
     * /!\ This route is under construction.
     * <br>
     * It will retrieve and create the Tuleap repositories from a Gitlab group
     *
     * @url    POST
     * @access protected
     *
     * @param GitlabGroupPOSTRepresentation $gitlab_group_link_representation {@from body}
     *
     * @return GitlabGroupRepresentation {@type GitlabGroupRepresentation}
     * @status 201
     *
     * @throws RestException 404
     * @throws RestException 401
     * @throws RestException 400
     */
    protected function createGroup(GitlabGroupPOSTRepresentation $gitlab_group_link_representation): GitlabGroupRepresentation
    {
        $this->options();
        $group_api_token   = GroupApiToken::buildNewGroupToken(new ConcealedString($gitlab_group_link_representation->gitlab_token));
        $gitlab_server_url = $gitlab_group_link_representation->gitlab_server_url;

        $project     = $this->getProjectById($gitlab_group_link_representation->project_id);
        $credentials = new Credentials($gitlab_server_url, $group_api_token);

        $current_user = UserManager::instance()->getCurrentUser();
        if (! $this->getGitPermissionsManager()->userIsGitAdmin($current_user, $project)) {
            throw new RestException(401, "User must be Git administrator.");
        }

        $gitlab_api_client = new ClientWrapper(
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new GitlabHTTPClientFactory(
                HttpClientFactory::createClient()
            )
        );

        $gitlab_backend_logger = BackendLogger::getDefaultLogger(gitlabPlugin::LOG_IDENTIFIER);

        $db_connection =  DBFactory::getMainTuleapDBConnection();

        $gitlab_repository_creator = new GitlabRepositoryCreator(
            new DBTransactionExecutorWithConnection(
                $db_connection
            ),
            new GitlabRepositoryIntegrationFactory(
                new GitlabRepositoryIntegrationDao(),
                ProjectManager::instance()
            ),
            new GitlabRepositoryIntegrationDao(),
            new WebhookCreator(
                new KeyFactory(),
                new WebhookDao(),
                new WebhookDeletor(
                    new WebhookDao(),
                    $gitlab_api_client,
                    $gitlab_backend_logger
                ),
                $gitlab_api_client,
                $gitlab_backend_logger,
            ),
            new IntegrationApiTokenInserter(new IntegrationApiTokenDao(), new KeyFactory())
        );

        $group_creation_handler = new GroupCreator(
            new GitlabProjectBuilder($gitlab_api_client),
            new GitlabGroupInformationRetriever($gitlab_api_client),
            new GitlabRepositoryGroupLinkHandler(
                new DBTransactionExecutorWithConnection($db_connection),
                new GitlabRepositoryIntegrationDao(),
                $gitlab_repository_creator,
                new GitlabGroupFactory(new GitlabGroupDAO()),
                new GroupTokenInserter(new GroupApiTokenDAO(), new KeyFactory())
            )
        );

        return $group_creation_handler->createGroupAndIntegrations($credentials, $gitlab_group_link_representation, $project);
    }

    /**
     * @throws RestException
     */
    private function getProjectById(int $project_id): Project
    {
        $project = ProjectManager::instance()->getProject($project_id);
        if (! $project || $project->isError()) {
            throw new RestException(404, "Project #$project_id not found.");
        }

        return $project;
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
