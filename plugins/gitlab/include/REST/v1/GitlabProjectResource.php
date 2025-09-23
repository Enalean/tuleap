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

use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;

final class GitlabProjectResource extends AuthenticatedResource
{
    private const int MAX_LIMIT = 50;

    /**
     * @url    OPTIONS {id}/gitlab_repositories
     *
     * @param int $id Id of the project
     */
    public function optionsGitlabRepositories(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * GET Gitlab Integrations.
     *
     * /!\ This route is under construction.
     * <br>
     * Retrieve all Gitlab integration for a given project.
     *
     * @url    GET {id}/gitlab_repositories
     * @access hybrid
     *
     * @param int $id Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return GitlabRepositoryRepresentation[]
     */
    public function getGitlabRepositories(
        int $id,
        int $limit = 10,
        int $offset = 0,
    ): array {
        if ($limit > self::MAX_LIMIT) {
            throw new RestException(400);
        }

        $this->checkAccess();

        $user    = \UserManager::instance()->getCurrentUser();
        $project = $this->getProject($id, $user);

        $repository_integration_factory = new GitlabRepositoryIntegrationFactory(
            new GitlabRepositoryIntegrationDao(),
            ProjectManager::instance()
        );

        $webhook_dao = new WebhookDao();

        $representation_factory = new GitlabRepositoryRepresentationFactory(
            $repository_integration_factory,
            $webhook_dao
        );

        $gitlab_repositories_representations = $representation_factory->getAllIntegrationsRepresentationsInProject(
            $project
        );

        $this->optionsGitlabRepositories($id);
        Header::sendPaginationHeaders($limit, $offset, count($gitlab_repositories_representations), self::MAX_LIMIT);

        return array_slice(
            $gitlab_repositories_representations,
            $offset,
            $limit
        );
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getProject(int $id, PFUser $user): Project
    {
        $project = ProjectManager::instance()->getProject($id);
        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());

        return $project;
    }
}
