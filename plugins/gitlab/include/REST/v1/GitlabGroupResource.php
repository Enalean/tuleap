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
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Group\GitlabGroupDAO;
use Tuleap\Gitlab\Group\GitlabGroupFactory;
use Tuleap\Gitlab\Group\GroupCreator;
use Tuleap\Gitlab\Group\GroupRepositoryIntegrationDAO;
use Tuleap\Gitlab\Group\GroupUpdator;
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
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupLinkRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPATCHRepresentation;
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
     * Link a GitLab group with Tuleap.
     *
     * /!\ This route is under construction.
     * <br>
     * It will retrieve and create the Tuleap repositories from a GitLab group
     *
     * @url    POST
     * @access protected
     *
     * @param GitlabGroupPOSTRepresentation $gitlab_group_link_representation {@from body}
     *
     * @return GitlabGroupRepresentation {@type GitlabGroupRepresentation}
     * @status 200
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
        $transaction_executor  = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $integration_dao       = new GitlabRepositoryIntegrationDao();
        $key_factory           = new KeyFactory();
        $group_dao             = new GitlabGroupDAO();

        $gitlab_repository_creator = new GitlabRepositoryCreator(
            $transaction_executor,
            new GitlabRepositoryIntegrationFactory(
                $integration_dao,
                ProjectManager::instance()
            ),
            $integration_dao,
            new WebhookCreator(
                $key_factory,
                new WebhookDao(),
                new WebhookDeletor(
                    new WebhookDao(),
                    $gitlab_api_client,
                    $gitlab_backend_logger
                ),
                $gitlab_api_client,
                $gitlab_backend_logger,
            ),
            new IntegrationApiTokenInserter(new IntegrationApiTokenDao(), $key_factory)
        );

        $group_creation_handler = new GroupCreator(
            new GitlabProjectBuilder($gitlab_api_client),
            new GitlabGroupInformationRetriever($gitlab_api_client),
            new GitlabRepositoryGroupLinkHandler(
                $transaction_executor,
                $integration_dao,
                $gitlab_repository_creator,
                new GitlabGroupFactory($group_dao, $group_dao, $group_dao),
                new GroupTokenInserter(new GroupApiTokenDAO(), $key_factory),
                new GroupRepositoryIntegrationDAO(),
                new CreateBranchPrefixDao()
            )
        );

        return $group_creation_handler->createGroupAndIntegrations($credentials, $gitlab_group_link_representation, $project);
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * Update a GitLab group link with Tuleap.
     *
     * /!\ This route is under construction.
     * <br>
     * It will update a GitLab group integration.
     *
     * <p>To update the prefix used in the branch creation for repositories that come with the linked group (feature flag must be enabled):</p>
     * <pre>
     * {<br>
     *   &nbsp;"create_branch_prefix" : "dev-"<br>
     * }<br>
     * </pre>
     *
     * <p>To update the artifact closure for repositories that come with the linked group (feature flag must be enabled):</p>
     * <pre>
     * {<br>
     *   &nbsp;"allow_artifact_closure" : false<br>
     * }<br>
     * </pre>
     *
     * <p>Both parameters can be updated in the same query (feature flag must be enabled):</p>
     * <pre>
     * {<br>
     *   &nbsp;"create_branch_prefix" : "dev-",<br>
     *   &nbsp;"allow_artifact_closure" : false<br>
     * }<br>
     * </pre>
     *
     * @url    PATCH {id}
     * @access protected
     *
     * @param int $id Id of the GitLab group link
     * @param GitlabGroupPATCHRepresentation $gitlab_group_link_representation {@from body}
     *
     * @return GitlabGroupLinkRepresentation {@type GitlabGroupLinkRepresentation}
     * @status 200
     *
     * @throws RestException 404
     * @throws RestException 401
     * @throws RestException 400
     */
    protected function updateGroupLink(int $id, GitlabGroupPATCHRepresentation $gitlab_group_link_representation): GitlabGroupLinkRepresentation
    {
        $this->optionsId($id);

        $group_dao = new GitlabGroupDAO();

        $gitlab_group_link = $group_dao->retrieveGroupLink($id);
        if (! $gitlab_group_link) {
            throw new RestException(404, "GitLab group link not found");
        }

        $project      = $this->getProjectById($gitlab_group_link->project_id);
        $current_user = UserManager::instance()->getCurrentUser();
        if (! $this->getGitPermissionsManager()->userIsGitAdmin($current_user, $project)) {
            throw new RestException(401, "User must be Git administrator.");
        }

        (new GroupUpdator($group_dao, $group_dao))->updateGroupLinkFromPATCHRequest(
            $gitlab_group_link,
            $gitlab_group_link_representation
        );

        $updated_gitlab_group_link = $group_dao->retrieveGroupLink($id);
        if (! $updated_gitlab_group_link) {
            throw new RestException(500, "Did not find the GitLab group link we've just updated");
        }
        return GitlabGroupLinkRepresentation::buildFromObject($updated_gitlab_group_link);
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
