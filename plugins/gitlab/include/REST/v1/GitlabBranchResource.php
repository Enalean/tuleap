<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

use Cocur\Slugify\Slugify;
use gitlabPlugin;
use Luracast\Restler\RestException;
use PluginManager;
use ProjectManager;
use Tracker_ArtifactFactory;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Artifact\BranchNameCreatorFromArtifact;
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

class GitlabBranchResource
{
    public const string ROUTE = 'gitlab_branch';

     /**
      * @url OPTIONS
      */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a GitLab branch.
     *
     * /!\ This route is under construction.
     * <br>
     * Create a branch in a GitLab integration.
     * The branch name is defined by Tuleap. The name will be like {prefix}TULEAP-{artifact_id}
     *
     * <br>
     * <br>
     * A GitLab branch can be created like:
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"gitlab_integration_id": 1,<br>
     *   &nbsp;"artifact_id": 123,<br>
     *   &nbsp;"reference": "main"<br>
     *  }<br>
     * </pre>
     *
     *
     * @url    POST
     * @access protected
     *
     * @param GitlabBranchPOSTRepresentation $gitlab_branch {@from body}
     *
     * @status 201
     *
     * @throws RestException 400
     * @throws RestException 404
     * @throws RestException 500
     */
    protected function createGitlabBranch(GitlabBranchPOSTRepresentation $gitlab_branch): GitlabBranchRepresentation
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

        $branch_creator = new GitlabBranchCreator(
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
            $gitlab_api_client,
            new BranchNameCreatorFromArtifact(
                new Slugify(),
                new CreateBranchPrefixDao()
            )
        );

        return $branch_creator->createBranchInGitlab(
            $current_user,
            $gitlab_branch
        );
    }
}
