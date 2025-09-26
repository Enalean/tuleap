<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\REST\v1;

use Git_PermissionsDao;
use Git_SystemEventManager;
use GitDao;
use GitPermissionsManager;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectManager;
use SystemEventManager;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;
use UserManager;

final class GitJenkinsServersResource extends AuthenticatedResource
{
    private const int DEFAULT_LIMIT  = 10;
    private const int MAX_LIMIT      = 50;
    private const int DEFAULT_OFFSET = 0;

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var GitPermissionsManager
     */
    private $git_permission_manager;
    /**
     * @var JenkinsServerDao
     */
    private $jenkins_server_dao;

    public function __construct()
    {
        $this->jenkins_server_dao = new JenkinsServerDao();

        $git_dao               = new GitDao();
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();

        $git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
        );

        $fine_grained_dao       = new FineGrainedDao();
        $fine_grained_retriever = new FineGrainedRetriever($fine_grained_dao);

        $this->git_permission_manager = new GitPermissionsManager(
            new Git_PermissionsDao(),
            $git_system_event_manager,
            $fine_grained_dao,
            $fine_grained_retriever
        );
    }

    /**
     * @url OPTIONS /{id}/git_jenkins_servers
     * @access protected
     */
    protected function optionsGitJenkinsServers(
        int $id,
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET,
    ): void {
        Header::allowOptionsGet();
    }

    /**
     * Get all Git Jenkins servers that are available in the projects
     *
     * @url GET /{id}/git_jenkins_servers
     * @access hybrid
     *
     * @param int $id Id of the project
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 100}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     *
     * @throws RestException
     */
    protected function getGitJenkinsServers(
        int $id,
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET,
    ): JenkinsServerRepresentationCollection {
        $user    = $this->user_manager->getCurrentUser();
        $project = $this->project_manager->getProject($id);

        $this->checkAccess();
        $this->checkUserCanAccessProject($project, $user);
        $this->checkUserIsGitAdministrator($project, $user);

        $this->optionsGitJenkinsServers($id, $limit, $offset);

        $servers = [];
        $results = $this->jenkins_server_dao->getPaginatedJenkinsServerOfProject(
            (int) $project->getID(),
            $limit,
            $offset
        );
        $total   = $this->jenkins_server_dao->foundRows();

        foreach ($results as $server) {
            $servers[] = new JenkinsServerRepresentation(
                $server['api_id'],
                $server['jenkins_server_url'],
            );
        }

        $jenkins_server_representation_collection = JenkinsServerRepresentationCollection::build(
            $servers,
            $total
        );

        Header::sendPaginationHeaders($limit, $offset, $total, self::MAX_LIMIT);
        return $jenkins_server_representation_collection;
    }

    /**
     * @throws RestException 404
     */
    private function checkUserCanAccessProject(Project $project, PFUser $user): void
    {
        ProjectAuthorization::userCanAccessProject(
            $user,
            $project,
            new URLVerification()
        );
    }

    /**
     * @throws RestException 401
     */
    private function checkUserIsGitAdministrator(Project $project, PFUser $user): void
    {
        if (! $this->git_permission_manager->userIsGitAdmin($user, $project)) {
            throw new RestException(401);
        }
    }
}
