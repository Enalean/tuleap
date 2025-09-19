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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use gitlabPlugin;
use Luracast\Restler\RestException;
use PluginManager;
use ProjectManager;
use Tracker_ArtifactFactory;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\Artifact\MergeRequestTitleCreatorFromArtifact;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\REST\Header;
use UserManager;

final class GitlabMergeRequestResource
{
    public const string ROUTE = 'gitlab_merge_request';

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a GitLab merge request.
     *
     * /!\ This route is under construction.
     * <br>
     * Create a merge request in a GitLab integration.
     * The merge request title is defined by Tuleap. The title will be like TULEAP-{artifact_id}: {artifact_title}
     * The merge request target branch is the GitLab repository default branch.
     * The merge request will be in draft.
     *
     * <br>
     * <br>
     * A GitLab merge request can be created like:
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"gitlab_integration_id": 1,<br>
     *   &nbsp;"artifact_id": 123,<br>
     *   &nbsp;"source_branch": "dev_TULEAP-123",<br>
     *  }<br>
     * </pre>
     *
     * @url    POST
     * @access protected
     *
     * @param GitlabMergeRequestPOSTRepresentation $gitlab_merge_request {@from body}
     *
     * @status 201
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function createGitlabMergeRequest(GitlabMergeRequestPOSTRepresentation $gitlab_merge_request): void
    {
        $this->options();

        $current_user   = UserManager::instance()->getCurrentUser();
        $plugin_manager = PluginManager::instance();
        $gitlab_plugin  = $plugin_manager->getPluginByName(gitlabPlugin::SERVICE_NAME);
        assert($gitlab_plugin instanceof gitlabPlugin);
        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);

        $gitlab_merge_request_creator = new GitlabMergeRequestCreator(
            Tracker_ArtifactFactory::instance(),
            new GitlabIntegrationAvailabilityChecker(
                $plugin_manager,
                $gitlab_plugin
            ),
            new GitlabRepositoryIntegrationFactory(
                new GitlabRepositoryIntegrationDao(),
                ProjectManager::instance()
            ),
            new CredentialsRetriever(
                new IntegrationApiTokenRetriever(
                    new IntegrationApiTokenDao(),
                    new KeyFactory()
                )
            ),
            new GitlabProjectBuilder($gitlab_api_client),
            $gitlab_api_client,
            new MergeRequestTitleCreatorFromArtifact()
        );

        $gitlab_merge_request_creator->createMergeRequestInGitlab(
            $current_user,
            $gitlab_merge_request
        );
    }
}
