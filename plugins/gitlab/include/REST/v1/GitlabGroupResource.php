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
use gitlabPlugin;
use GitPermissionsManager;
use Luracast\Restler\RestException;
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
use Tuleap\Gitlab\Core\ProjectRetriever;
use Tuleap\Gitlab\Group\GitlabServerURIDeducer;
use Tuleap\Gitlab\Group\GroupLinkDAO;
use Tuleap\Gitlab\Group\GroupLinkFactory;
use Tuleap\Gitlab\Group\GroupLinkCreator;
use Tuleap\Gitlab\Group\GroupLinkCredentialsRetriever;
use Tuleap\Gitlab\Group\GroupLinkRetriever;
use Tuleap\Gitlab\Group\GroupLinkSynchronizer;
use Tuleap\Gitlab\Group\GroupLinkUpdateHandler;
use Tuleap\Gitlab\Group\GroupLinkRepositoryIntegrationDAO;
use Tuleap\Gitlab\Group\GroupUnlinkHandler;
use Tuleap\Gitlab\Group\GroupLinkUpdater;
use Tuleap\Gitlab\Group\SynchronizeGroupLinkCommand;
use Tuleap\Gitlab\Group\Token\GroupLinkApiToken;
use Tuleap\Gitlab\Group\Token\GroupLinkApiTokenDAO;
use Tuleap\Gitlab\Group\Token\GroupLinkTokenInserter;
use Tuleap\Gitlab\Group\Token\GroupLinkTokenUpdater;
use Tuleap\Gitlab\Group\Token\GroupLinkTokenRetriever;
use Tuleap\Gitlab\Group\UpdateGroupLinkCommand;
use Tuleap\Gitlab\Permission\GitAdministratorChecker;
use Tuleap\Gitlab\Repository\GitlabProjectIntegrator;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreator;
use Tuleap\Gitlab\Repository\GitlabRepositoryGroupLinkHandler;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\RepositoryIntegrationRetriever;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupLinkRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupLinkSynchronizedRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPATCHRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\NeverThrow\Result;
use Tuleap\REST\Header;
use UserManager;

final class GitlabGroupResource
{
    public const string ROUTE = 'gitlab_groups';

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Link a GitLab group to a Tuleap project.
     *
     * It will link a GitLab group to a Tuleap project. It will retrieve all the GitLab projects of the group and integrate
     * them with Tuleap.
     * <br>
     * Use an empty string for `create_branch_prefix` if you don't want any prefix: `"create_branch_prefix": ""`.
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
     * @throws RestException 403
     * @throws RestException 400
     */
    protected function createGroup(
        GitlabGroupPOSTRepresentation $gitlab_group_link_representation,
    ): GitlabGroupRepresentation {
        $this->options();
        $current_user = UserManager::instance()->getCurrentUser();
        $retriever    = new ProjectRetriever(ProjectManager::instance());
        $checker      = new GitAdministratorChecker($this->getGitPermissionsManager());

        return $retriever
            ->retrieveProject($gitlab_group_link_representation->project_id)
            ->andThen(
                fn(\Project $project) => $checker->checkUserIsGitAdministrator($project, $current_user)->map(
                    static fn() => $project
                )
            )
            ->andThen(
                function (\Project $project) use ($gitlab_group_link_representation) {
                    $group_api_token   = GroupLinkApiToken::buildNewGroupToken(
                        new ConcealedString($gitlab_group_link_representation->gitlab_token)
                    );
                    $gitlab_server_url = $gitlab_group_link_representation->gitlab_server_url;

                    $credentials = new Credentials($gitlab_server_url, $group_api_token);

                    $gitlab_api_client = new ClientWrapper(
                        HTTPFactoryBuilder::requestFactory(),
                        HTTPFactoryBuilder::streamFactory(),
                        new GitlabHTTPClientFactory(
                            HttpClientFactory::createClient()
                        )
                    );

                    $gitlab_backend_logger = BackendLogger::getDefaultLogger(gitlabPlugin::LOG_IDENTIFIER);
                    $transaction_executor  = new DBTransactionExecutorWithConnection(
                        DBFactory::getMainTuleapDBConnection()
                    );
                    $integration_dao       = new GitlabRepositoryIntegrationDao();
                    $key_factory           = new KeyFactory();
                    $group_dao             = new GroupLinkDAO();

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

                    $create_branch_prefix_dao          = new CreateBranchPrefixDao();
                    $group_link_repository_integration = new GroupLinkRepositoryIntegrationDAO();

                    $gitlab_project_integrator = new GitlabProjectIntegrator(
                        $gitlab_repository_creator,
                        $create_branch_prefix_dao,
                        $group_link_repository_integration,
                        $group_link_repository_integration,
                        new RepositoryIntegrationRetriever(
                            $integration_dao
                        )
                    );

                    $group_creation_handler = new GroupLinkCreator(
                        new GitlabProjectBuilder($gitlab_api_client),
                        new GitlabGroupInformationRetriever($gitlab_api_client),
                        new GitlabRepositoryGroupLinkHandler(
                            $transaction_executor,
                            new GroupLinkFactory($group_dao, $group_dao, $group_dao),
                            new GroupLinkTokenInserter(new GroupLinkApiTokenDAO(), $key_factory),
                            $gitlab_project_integrator
                        )
                    );

                    return $group_creation_handler->createGroupAndIntegrations(
                        $credentials,
                        $gitlab_group_link_representation,
                        $project
                    );
                }
            )->match(fn(GitlabGroupRepresentation $representation) => $representation, [FaultMapper::class, 'mapToRestException']);
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Update a GitLab group linked with Tuleap.
     *
     * It will update a GitLab group integration.
     * <br>
     * <p>To update the prefix used in the branch creation for repositories that come with the linked group:</p>
     * <pre>
     * {<br>
     *   &nbsp;"create_branch_prefix": "dev-"<br>
     * }
     * </pre>
     * <p>Use an empty string for `create_branch_prefix` if you want to remove a prefix: `"create_branch_prefix": ""`.</p>
     *
     * <p>To update the artifact closure for repositories that come with the linked group:</p>
     * <pre>
     * {<br>
     *   &nbsp;"allow_artifact_closure": false<br>
     * }
     * </pre>
     *
     * <p>To update the Gitlab token of the linked group:</p>
     * <pre>
     * {<br>
     *   &nbsp;"gitlab_token": "my_t0k3n"<br>
     * }
     * </pre>
     *
     * <p>All parameters can be updated at once:</p>
     * <pre>
     * {<br>
     *   &nbsp;"create_branch_prefix": "dev-",<br>
     *   &nbsp;"allow_artifact_closure": false,<br>
     *   &nbsp;"gitlab_token": "my_t0k3n"<br>
     * }
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
     * @throws RestException 403
     * @throws RestException 400
     */
    protected function updateGroupLink(
        int $id,
        GitlabGroupPATCHRepresentation $gitlab_group_link_representation,
    ): GitlabGroupLinkRepresentation {
        $this->optionsId($id);

        $group_api_token = $gitlab_group_link_representation->gitlab_token ? GroupLinkApiToken::buildNewGroupToken(
            new ConcealedString($gitlab_group_link_representation->gitlab_token)
        ) : null;


        $current_user = UserManager::instance()->getCurrentUser();
        $group_dao    = new GroupLinkDAO();
        $handler      = new GroupLinkUpdateHandler(
            new ProjectRetriever(\ProjectManager::instance()),
            new GitAdministratorChecker($this->getGitPermissionsManager()),
            new GroupLinkRetriever($group_dao),
            new GroupLinkUpdater($group_dao, $group_dao, new GroupLinkTokenUpdater(new GroupLinkApiTokenDAO(), new KeyFactory()))
        );
        return $handler->handleGroupLinkUpdate(
            new UpdateGroupLinkCommand(
                $id,
                $gitlab_group_link_representation->create_branch_prefix,
                $gitlab_group_link_representation->allow_artifact_closure,
                $group_api_token,
                $current_user
            )
        )
            ->match(
                [GitlabGroupLinkRepresentation::class, 'buildFromObject'],
                [FaultMapper::class, 'mapToRestException']
            );
    }

    /**
     * Unlink the Tuleap Project and the GitLab group.
     *
     * It will not delete the Group on GitLab side. It deletes the link between the Tuleap Project and the GitLab group.
     * All GitLab projects part of the group will stay integrated with Tuleap, their configuration will not change.
     * If you wish to remove the integrations of the GitLab projects part of this group, please call DELETE  `/gitlab_repositories/{id}`
     * for each one.
     *
     * @url    DELETE {id}
     * @access protected
     *
     * @param int $id Id of the GitLab group link
     * @status 200
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function deleteGroupLink(int $id): void
    {
        $this->optionsId($id);

        $current_user = UserManager::instance()->getCurrentUser();
        $group_dao    = new GroupLinkDAO();
        $unlinker     = new GroupUnlinkHandler(
            new ProjectRetriever(ProjectManager::instance()),
            new GitAdministratorChecker($this->getGitPermissionsManager()),
            new GroupLinkRetriever($group_dao),
            $group_dao
        );

        $result = $unlinker->unlinkProjectAndGroup($id, $current_user);
        if (Result::isErr($result)) {
            FaultMapper::mapToRestException($result->error);
        }
    }

    /**
     * @url OPTIONS {id}/synchronize
     */
    public function optionsSynchronizeGroupLink(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Synchronize GitLab projects of a group with Tuleap
     *
     * It will retrieve all the GitLab projects of the group and integrate them with Tuleap.
     *
     * Please note that Group link settings such as the branch prefix name will only be applied to
     * GitLab projects that were <strong>created since last synchronisation</strong>.
     * Existing integrations' settings will not change after synchronization.
     *
     * <p><strong>Note:</strong> If a GitLab project is removed on the GitLab side, the group link and
     * the repository integration on the Tuleap side will not be removed.</p>
     *
     * @url    POST {id}/synchronize
     * @access protected
     *
     * @param int $id Id of the GitLab group link
     * @return GitlabGroupLinkSynchronizedRepresentation {@type GitlabGroupLinkSynchronizedRepresentation}
     * @status 200
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function postSynchronizeGroupLink(int $id): GitlabGroupLinkSynchronizedRepresentation
    {
        $this->optionsSynchronizeGroupLink($id);

        $current_user = UserManager::instance()->getCurrentUser();

        $group_dao                         = new GroupLinkDAO();
        $group_token_dao                   = new GroupLinkApiTokenDAO();
        $integration_dao                   = new GitlabRepositoryIntegrationDao();
        $create_branch_prefix_dao          = new CreateBranchPrefixDao();
        $group_link_repository_integration = new GroupLinkRepositoryIntegrationDAO();

        $transaction_executor = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );

        $key_factory = new KeyFactory();

        $gitlab_api_client = new ClientWrapper(
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new GitlabHTTPClientFactory(
                HttpClientFactory::createClient()
            )
        );

        $gitlab_backend_logger = BackendLogger::getDefaultLogger(gitlabPlugin::LOG_IDENTIFIER);

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

        $gitlab_project_integrator = new GitlabProjectIntegrator(
            $gitlab_repository_creator,
            $create_branch_prefix_dao,
            $group_link_repository_integration,
            $group_link_repository_integration,
            new RepositoryIntegrationRetriever(
                $integration_dao
            )
        );

        $synchronizer = new GroupLinkSynchronizer(
            $transaction_executor,
            new GroupLinkRetriever($group_dao),
            new GroupLinkCredentialsRetriever(
                new GitlabServerURIDeducer(HTTPFactoryBuilder::URIFactory()),
                new GroupLinkTokenRetriever($group_token_dao, $key_factory),
            ),
            new GitlabProjectBuilder($gitlab_api_client),
            $group_dao,
            $gitlab_project_integrator,
            new ProjectRetriever(ProjectManager::instance()),
            new GitAdministratorChecker($this->getGitPermissionsManager()),
        );

        return $synchronizer->synchronizeGroupLink(new SynchronizeGroupLinkCommand($id, $current_user))->match(
            [GitlabGroupLinkSynchronizedRepresentation::class, 'fromGroupLinkSynchronized'],
            [FaultMapper::class, 'mapToRestException']
        );
    }

    private function getGitPermissionsManager(): GitPermissionsManager
    {
        $git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
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
