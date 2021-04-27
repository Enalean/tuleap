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
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryAlreadyIntegratedInProjectException;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreator;
use Tuleap\Gitlab\Repository\GitlabRepositoryDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryDeletor;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryNotInProjectException;
use Tuleap\Gitlab\Repository\GitlabRepositoryNotIntegratedInAnyProjectException;
use Tuleap\Gitlab\Repository\GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiToken;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenDao;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenInserter;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\REST\Header;
use UserManager;

final class GitlabRepositoryResource
{
    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsPostPatch();
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

        $gitlab_server_url    = $gitlab_repository->gitlab_server_url;
        $bot_api_token        = GitlabBotApiToken::buildBrandNewToken(new ConcealedString($gitlab_repository->gitlab_bot_api_token));
        $project_id           = $gitlab_repository->project_id;
        $gitlab_repository_id = $gitlab_repository->gitlab_repository_id;

        $project     = $this->getProjectById($project_id);
        $credentials = new Credentials($gitlab_server_url, $bot_api_token);

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
                new GitlabRepositoryFactory(
                    new GitlabRepositoryDao()
                ),
                new GitlabRepositoryDao(),
                new GitlabRepositoryProjectDao(),
                new WebhookCreator(
                    new KeyFactory(),
                    new WebhookDao(),
                    new WebhookDeletor(
                        new WebhookDao(),
                        $gitlab_api_client,
                        BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER)
                    ),
                    $gitlab_api_client,
                    new InstanceBaseURLBuilder(),
                    BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER),
                ),
                new GitlabBotApiTokenInserter(new GitlabBotApiTokenDao(), new KeyFactory())
            );

            $integrated_gitlab_repository = $gitlab_repository_creator->integrateGitlabRepositoryInProject(
                $credentials,
                $gitlab_api_project,
                $project
            );

            return new GitlabRepositoryRepresentation(
                $integrated_gitlab_repository->getId(),
                $integrated_gitlab_repository->getGitlabRepositoryId(),
                $integrated_gitlab_repository->getName(),
                $integrated_gitlab_repository->getDescription(),
                $integrated_gitlab_repository->getGitlabRepositoryUrl(),
                $integrated_gitlab_repository->getLastPushDate()->getTimestamp(),
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
     * Update GitLab integration
     *
     * <p>To update the bot api token, used by Tuleap to communicate with GitLab:</p>
     * <pre>
     * {<br>
     *   &nbsp;"update_bot_api_token": {<br>
     *   &nbsp;&nbsp;&nbsp;"gitlab_bot_api_token" : "The new token",<br>
     *   &nbsp;&nbsp;&nbsp;"gitlab_repository_id" : 145896<br>
     *   &nbsp;&nbsp;&nbsp;"gitlab_repository_url" : "https://example.com/project/url"<br>
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
     *   &nbsp;"generate_new_secret": {<br>
     *   &nbsp;&nbsp;&nbsp;"gitlab_repository_id" : 145896<br>
     *   &nbsp;&nbsp;&nbsp;"gitlab_repository_url" : "https://example.com/project/url"<br>
     *   &nbsp;}<br>
     * }<br>
     * </pre>
     *
     * <p>
     * <strong>Note:</strong> You cannot at the same time update the token and update the secret.
     * You will get a <code>400</code> if you send both <code>update_bot_api_token</code> and <code>generate_new_secret</code>.
     * </p>
     *
     * @url    PATCH
     * @access protected
     *
     * @param GitlabRepositoryPatchRepresentation $patch_representation {@from body}
     */
    protected function patch(GitlabRepositoryPatchRepresentation $patch_representation): void
    {
        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);
        $logger                = BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER);

        $current_user = UserManager::instance()->getCurrentUser();

        if ($patch_representation->update_bot_api_token && $patch_representation->generate_new_secret) {
            throw new RestException(400, 'You cannot ask at the same time to update the api token and generate a new webhook secret');
        }

        if ($patch_representation->update_bot_api_token) {
            $bot_api_token_updater = new BotApiTokenUpdater(
                new GitlabRepositoryFactory(
                    new GitlabRepositoryDao()
                ),
                new GitlabProjectBuilder($gitlab_api_client),
                new GitlabRepositoryProjectRetriever(
                    new GitlabRepositoryProjectDao(),
                    ProjectManager::instance()
                ),
                $this->getGitPermissionsManager(),
                new GitlabBotApiTokenInserter(
                    new GitlabBotApiTokenDao(),
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
                    new InstanceBaseURLBuilder(),
                    $logger,
                ),
                $logger,
            );

            $bot_api_token_updater->update(
                new ConcealedBotApiTokenPatchRepresentation(
                    $patch_representation->update_bot_api_token->gitlab_repository_id,
                    $patch_representation->update_bot_api_token->gitlab_repository_url,
                    new ConcealedString($patch_representation->update_bot_api_token->gitlab_bot_api_token),
                ),
                $current_user,
            );
        }

        if ($patch_representation->generate_new_secret) {
            $generator = new WebhookSecretGenerator(
                new GitlabRepositoryFactory(
                    new GitlabRepositoryDao()
                ),
                new GitlabRepositoryProjectRetriever(
                    new GitlabRepositoryProjectDao(),
                    ProjectManager::instance()
                ),
                $this->getGitPermissionsManager(),
                new CredentialsRetriever(
                    new GitlabBotApiTokenRetriever(
                        new GitlabBotApiTokenDao(),
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
                    new InstanceBaseURLBuilder(),
                    $logger,
                )
            );

            $generator->regenerate($patch_representation->generate_new_secret, $current_user);
        }
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the GitLab repository integration
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsDelete();
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
        $this->optionsId($id);

        $repository_factory = new GitlabRepositoryFactory(
            new GitlabRepositoryDao()
        );

        $gitlab_repository = $repository_factory->getGitlabRepositoryById($id);

        if ($gitlab_repository === null) {
            throw new RestException(404, "Repository #$id not found.");
        }

        $project = $this->getProjectById($project_id);

        $current_user = UserManager::instance()->getCurrentUser();

        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);

        $deletor = new GitlabRepositoryDeletor(
            $this->getGitPermissionsManager(),
            new DBTransactionExecutorWithConnection(
                DBFactory::getMainTuleapDBConnection()
            ),
            new GitlabRepositoryProjectDao(),
            new WebhookDeletor(
                new WebhookDao(),
                $gitlab_api_client,
                BackendLogger::getDefaultLogger(\gitlabPlugin::LOG_IDENTIFIER)
            ),
            new GitlabRepositoryDao(),
            new GitlabBotApiTokenDao(),
            new CommitTuleapReferenceDao(),
            new MergeRequestTuleapReferenceDao(),
            new TagInfoDao(),
            new CredentialsRetriever(new GitlabBotApiTokenRetriever(new GitlabBotApiTokenDao(), new KeyFactory()))
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
}
