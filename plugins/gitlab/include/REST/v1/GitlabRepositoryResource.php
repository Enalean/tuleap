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

use BackendLogger;
use Git_PermissionsDao;
use Git_SystemEventManager;
use GitDao;
use GitPermissionsManager;
use GitRepositoryFactory;
use GitUserNotAdminException;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use SystemEventManager;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixUpdater;
use Tuleap\Gitlab\Repository\GitlabRepositoryAlreadyIntegratedInProjectException;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreator;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreatorConfiguration;
use Tuleap\Gitlab\Repository\GitlabRepositoryDeletor;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationUpdator;
use Tuleap\Gitlab\Repository\GitlabRepositoryNotInProjectException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationNotFoundException;
use Tuleap\Gitlab\Repository\GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException;
use Tuleap\Gitlab\Repository\Token\IntegrationApiToken;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\REST\Header;
use UserManager;

final class GitlabRepositoryResource
{
    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Integrate a GitLab repository into a project.
     *
     * /!\ This route is under construction.
     * <br>
     * Integrate the given GitLab repository into project.
     *
     * <br>
     * <br>
     * A GitLab repository can be integrated into a project like:
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"project_id": 122,<br>
     *   &nbsp;"gitlab_server_url" : "https://example.com",<br>
     *   &nbsp;"gitlab_bot_api_token" : "project_bot_token",<br>
     *   &nbsp;"gitlab_repository_id" : 145896<br>
     *  }<br>
     * </pre>
     *
     *
     * @url    POST
     * @access protected
     *
     * @param GitlabRepositoryPOSTRepresentation $gitlab_repository {@from body}
     *
     * @status 201
     * @return GitlabRepositoryRepresentation {@type GitlabRepositoryRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     *
     */
    protected function createGitlabRepository(GitlabRepositoryPOSTRepresentation $gitlab_repository): GitlabRepositoryRepresentation
    {
        $this->options();

        $gitlab_server_url     = $gitlab_repository->gitlab_server_url;
        $integration_api_token = IntegrationApiToken::buildBrandNewToken(new ConcealedString($gitlab_repository->gitlab_bot_api_token));
        $project_id            = $gitlab_repository->project_id;
        $gitlab_repository_id  = $gitlab_repository->gitlab_repository_id;

        $project     = $this->getProjectById($project_id);
        $credentials = new Credentials($gitlab_server_url, $integration_api_token);

        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);

        $current_user = UserManager::instance()->getCurrentUser();
        if (! $this->getGitPermissionsManager()->userIsGitAdmin($current_user, $project)) {
            throw new RestException(401, "User must be Git administrator.");
        }

        try {
            $gitlab_api_project = (new GitlabProjectBuilder($gitlab_api_client))->getProjectFromGitlabAPI(
                $credentials,
                $gitlab_repository_id
            );

            $gitlab_repository_creator = new GitlabRepositoryCreator(
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
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
                        BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER)
                    ),
                    $gitlab_api_client,
                    BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER),
                ),
                new IntegrationApiTokenInserter(new IntegrationApiTokenDao(), new KeyFactory())
            );

            if (isset($gitlab_repository->allow_artifact_closure) && $gitlab_repository->allow_artifact_closure === true) {
                $configuration = GitlabRepositoryCreatorConfiguration::buildConfigurationAllowingArtifactClosure();
            } else {
                $configuration = GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration();
            }

            $integrated_gitlab_repository = $gitlab_repository_creator->integrateGitlabRepositoryInProject(
                $credentials,
                $gitlab_api_project,
                $project,
                $configuration
            );

            $webhook_dao         = new WebhookDao();
            $integration_webhook = $webhook_dao->getGitlabRepositoryWebhook(
                $integrated_gitlab_repository->getId()
            );

            $create_branch_prefix = (new CreateBranchPrefixDao())->searchCreateBranchPrefixForIntegration(
                $integrated_gitlab_repository->getId()
            );

            return new GitlabRepositoryRepresentation(
                $integrated_gitlab_repository->getId(),
                $integrated_gitlab_repository->getGitlabRepositoryId(),
                $integrated_gitlab_repository->getName(),
                $integrated_gitlab_repository->getDescription(),
                $integrated_gitlab_repository->getGitlabRepositoryUrl(),
                $integrated_gitlab_repository->getLastPushDate()->getTimestamp(),
                $integrated_gitlab_repository->getProject(),
                $integrated_gitlab_repository->isArtifactClosureAllowed(),
                $integration_webhook !== null,
                $create_branch_prefix
            );
        } catch (
            GitlabResponseAPIException |
            GitlabRepositoryAlreadyIntegratedInProjectException |
            GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException $exception
        ) {
            throw new RestException(400, $exception->getMessage());
        } catch (GitlabRequestException $exception) {
            throw new RestException(
                $exception->getErrorCode(),
                $exception->getMessage()
            );
        }
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the GitLab repository integration
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Delete Gitlab Integrations.
     *
     * /!\ This route is under construction.
     * <br>
     * Delete the given GitLab integration.
     *
     * @url    DELETE {id}
     * @access protected
     *
     * @param int $id Id of the GitLab repository integration
     *
     * @status 204
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    protected function deleteGitlabRepository(int $id): void
    {
        $this->optionsId($id);

        $repository_integration_factory = new GitlabRepositoryIntegrationFactory(
            new GitlabRepositoryIntegrationDao(),
            ProjectManager::instance()
        );

        $gitlab_repository_integration = $repository_integration_factory->getIntegrationById($id);

        if ($gitlab_repository_integration === null) {
            throw new RestException(404, "Repository #$id not found.");
        }

        $current_user          = UserManager::instance()->getCurrentUser();
        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);

        $deletor = new GitlabRepositoryDeletor(
            $this->getGitPermissionsManager(),
            new DBTransactionExecutorWithConnection(
                DBFactory::getMainTuleapDBConnection()
            ),
            new WebhookDeletor(
                new WebhookDao(),
                $gitlab_api_client,
                BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER)
            ),
            new GitlabRepositoryIntegrationDao(),
            new IntegrationApiTokenDao(),
            new CommitTuleapReferenceDao(),
            new MergeRequestTuleapReferenceDao(),
            new TagInfoDao(),
            new BranchInfoDao(),
            new CredentialsRetriever(new IntegrationApiTokenRetriever(new IntegrationApiTokenDao(), new KeyFactory())),
            new CreateBranchPrefixDao()
        );

        try {
            $deletor->deleteRepositoryIntegration(
                $gitlab_repository_integration,
                $current_user
            );
        } catch (GitUserNotAdminException $exception) {
            throw new RestException(401, "User is not Git administrator.");
        } catch (GitlabRepositoryNotInProjectException | GitlabRepositoryIntegrationNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * Update GitLab integration
     *
     * <pre>
     * /!\ This route is under construction and subject to changes /!\
     * </pre>
     *
     * <p>To update the API token, used by Tuleap to communicate with GitLab:</p>
     * <pre>
     * {<br>
     *   &nbsp;"update_bot_api_token": {<br>
     *   &nbsp;&nbsp;&nbsp;"gitlab_api_token" : "The new token"<br>
     *   &nbsp;}<br>
     *  }<br>
     * </pre>
     * <br>
     * <p>
     * <strong>Note:</strong> To ensure that the new token has needed access, it will regenerate the webhook used by GitLab.
     * </p>
     *
     * <p>To update the webhook secret, used by GitLab to communicate with Tuleap:</p>
     * <pre>
     * {<br>
     *   &nbsp;"generate_new_secret": true
     * }<br>
     * </pre>
     *
     * <p>To update the artifact closure option :</p>
     * <pre>
     * {<br>
     *   &nbsp;"allow_artifact_closure" : false<br>
     * }<br>
     * </pre>
     *
     * <p>To update the prefix used in the branch creation (feature flag must be enabled):</p>
     * <pre>
     * {<br>
     *   &nbsp;"create_branch_prefix" : "dev-"<br>
     * }<br>
     * </pre>
     *
     * <p>
     * <strong>Note:</strong> You cannot do multiple actions at the same.
     * You will get a <code>400</code> if you send either <code>update_bot_api_token</code> or <code>generate_new_secret</code> or <code>allow_artifact_closure</code> or <code>create_branch_prefix</code>.
     * </p>
     *
     * @url    PATCH {id}
     * @access protected
     *
     * @param int                                 $id                   Id of the Gitlab integration
     * @param GitlabRepositoryPatchRepresentation $patch_representation {@from body}
     *
     * @return GitlabRepositoryRepresentation {@type GitlabRepositoryRepresentation}
     *
     * @throws RestException 401
     * @throws RestException 404
     * @throws RestException 500
     */
    protected function patchId(
        int $id,
        GitlabRepositoryPatchRepresentation $patch_representation,
    ): GitlabRepositoryRepresentation {
        $this->optionsId($id);

        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);
        $logger                = BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER);

        $current_user = UserManager::instance()->getCurrentUser();

        $this->validateJSONIsWellFormed($patch_representation);

        if ($patch_representation->update_bot_api_token) {
            $bot_api_token_updater = new BotApiTokenUpdater(
                new GitlabRepositoryIntegrationFactory(
                    new GitlabRepositoryIntegrationDao(),
                    ProjectManager::instance()
                ),
                new GitlabProjectBuilder($gitlab_api_client),
                $this->getGitPermissionsManager(),
                new IntegrationApiTokenInserter(
                    new IntegrationApiTokenDao(),
                    new KeyFactory()
                ),
                new WebhookCreator(
                    new KeyFactory(),
                    new WebhookDao(),
                    new WebhookDeletor(
                        new WebhookDao(),
                        $gitlab_api_client,
                        BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER)
                    ),
                    $gitlab_api_client,
                    $logger,
                ),
                $logger,
            );

            $bot_api_token_updater->update(
                new ConcealedBotApiTokenPatchRepresentation(
                    $id,
                    new ConcealedString($patch_representation->update_bot_api_token->gitlab_api_token),
                ),
                $current_user,
            );

            return $this->buildUpdatedIntegrationRepresentation($id);
        }

        if ($patch_representation->generate_new_secret && $patch_representation->generate_new_secret === true) {
            $generator = new WebhookSecretGenerator(
                new GitlabRepositoryIntegrationFactory(
                    new GitlabRepositoryIntegrationDao(),
                    ProjectManager::instance()
                ),
                $this->getGitPermissionsManager(),
                new CredentialsRetriever(
                    new IntegrationApiTokenRetriever(
                        new IntegrationApiTokenDao(),
                        new KeyFactory()
                    ),
                ),
                new WebhookCreator(
                    new KeyFactory(),
                    new WebhookDao(),
                    new WebhookDeletor(
                        new WebhookDao(),
                        $gitlab_api_client,
                        BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER)
                    ),
                    $gitlab_api_client,
                    $logger,
                )
            );

            $generator->regenerate($id, $current_user);

            return $this->buildUpdatedIntegrationRepresentation($id);
        }

        if (isset($patch_representation->allow_artifact_closure)) {
            $dao                                   = new GitlabRepositoryIntegrationDao();
            $gitlab_repository_integration_factory =   new GitlabRepositoryIntegrationFactory(
                $dao,
                ProjectManager::instance()
            );
            $updater                               = new GitlabRepositoryIntegrationUpdator(
                $dao,
                $this->getGitPermissionsManager(),
                $gitlab_repository_integration_factory,
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                ),
            );
            try {
                $updater->updateTuleapArtifactClosureOfAGitlabIntegration(
                    $id,
                    $patch_representation->allow_artifact_closure,
                    $current_user
                );

                return $this->buildUpdatedIntegrationRepresentation($id);
            } catch (GitUserNotAdminException $e) {
                throw new RestException(401, "User must be Git administrator.");
            } catch (GitlabRepositoryIntegrationNotFoundException $e) {
                throw new RestException(404, $e->getMessage());
            }
        }

        if ($patch_representation->create_branch_prefix !== null) {
            $prefix_updater = new CreateBranchPrefixUpdater(
                new GitlabRepositoryIntegrationFactory(
                    new GitlabRepositoryIntegrationDao(),
                    ProjectManager::instance(),
                ),
                $this->getGitPermissionsManager(),
                new CreateBranchPrefixDao(),
            );

            try {
                $prefix_updater->updateBranchPrefix(
                    $current_user,
                    $id,
                    $patch_representation->create_branch_prefix
                );

                return $this->buildUpdatedIntegrationRepresentation($id);
            } catch (InvalidBranchNameException $exception) {
                throw new RestException(
                    400,
                    $exception->getMessage()
                );
            } catch (GitUserNotAdminException $exception) {
                throw new RestException(401, "User must be Git administrator.");
            }
        }

        throw new RestException(400, "The JSON representation cannot be null");
    }

    /**
     * @url    OPTIONS {id}/branches
     * @access protected
     */
    protected function optionBranches(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get information on branches of the GitLab repository
     *
     * @url    GET {id}/branches
     * @access protected
     *
     * @param int $id ID of the GitLab integration
     *
     *
     * Only members of the project where the integration lives can access the branches information
     *
     * @throws RestException 404
     */
    protected function getBranches(int $id): BranchesInformationRepresentation
    {
        $this->optionBranches($id);

        $branch_information_retriever = new GitlabBranchInformationRetriever(
            new GitlabRepositoryIntegrationFactory(
                new GitlabRepositoryIntegrationDao(),
                ProjectManager::instance()
            ),
            new CredentialsRetriever(new IntegrationApiTokenRetriever(new IntegrationApiTokenDao(), new KeyFactory())),
            new GitlabProjectBuilder(
                new ClientWrapper(
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new GitlabHTTPClientFactory(HttpClientFactory::createClient())
                )
            )
        );

        return $branch_information_retriever->getBranchInformation(UserManager::instance()->getCurrentUser(), $id);
    }

    /**
     * @throws RestException
     */
    private function buildUpdatedIntegrationRepresentation(int $id): GitlabRepositoryRepresentation
    {
        $dao                                   = new GitlabRepositoryIntegrationDao();
        $gitlab_repository_integration_factory = new GitlabRepositoryIntegrationFactory(
            $dao,
            ProjectManager::instance()
        );

        $updated_gitlab_integration = $gitlab_repository_integration_factory->getIntegrationById($id);
        if ($updated_gitlab_integration === null) {
            throw new RestException(500, "Updated repository not found, this must not happen");
        }

        $webhook_dao         = new WebhookDao();
        $integration_webhook = $webhook_dao->getGitlabRepositoryWebhook(
            $updated_gitlab_integration->getId()
        );

        $create_branch_prefix = (new CreateBranchPrefixDao())->searchCreateBranchPrefixForIntegration($id);

        return new GitlabRepositoryRepresentation(
            $updated_gitlab_integration->getId(),
            $updated_gitlab_integration->getGitlabRepositoryId(),
            $updated_gitlab_integration->getName(),
            $updated_gitlab_integration->getDescription(),
            $updated_gitlab_integration->getGitlabRepositoryUrl(),
            $updated_gitlab_integration->getLastPushDate()->getTimestamp(),
            $updated_gitlab_integration->getProject(),
            $updated_gitlab_integration->isArtifactClosureAllowed(),
            $integration_webhook !== null,
            $create_branch_prefix
        );
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

    /**
     * @throws RestException
     */
    private function validateJSONIsWellFormed(GitlabRepositoryPatchRepresentation $patch_representation): void
    {
        $provided_parameter = 0;
        foreach (get_object_vars($patch_representation) as $parameter) {
            if ($parameter !== null) {
                $provided_parameter++;
            }
        }

        if ($provided_parameter > 1) {
            throw new RestException(
                400,
                'You cannot ask at the same time to update the api token, generate a new webhook secret, allowing artifact closure or updating the create branch prefix.'
            );
        }
    }
}
