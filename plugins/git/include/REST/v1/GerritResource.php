<?php
/*
 * Copyright (c) Enalean, 2016 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\Git\RemoteServer\Gerrit\Permission\ServerPermissionManager;
use Tuleap\Git\RemoteServer\Gerrit\Permission\ServerPermissionDao;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_Dao;
use GitDao;
use ProjectManager;
use SystemEventManager;
use GitRepositoryFactory;
use Git_SystemEventManager;
use Tuleap\REST\ProjectStatusVerificator;
use UserManager;
use PFUser;

class GerritResource extends AuthenticatedResource
{

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $server_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var ServerPermissionManager
     */
    private $server_permission_manager;

    public function __construct()
    {
        $git_dao               = new GitDao();
        $this->project_manager = ProjectManager::instance();
        $repository_factory    = new GitRepositoryFactory(
            $git_dao,
            $this->project_manager
        );

        $this->server_factory = new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            $git_dao,
            new Git_SystemEventManager(SystemEventManager::instance(), $repository_factory),
            $this->project_manager
        );

        $this->user_manager              = UserManager::instance();
        $this->server_permission_manager = new ServerPermissionManager(new ServerPermissionDao());
    }

    /**
     * Get Gerrit servers
     *
     * This route lists Gerrit servers for users that are allowed to see it:<br/>
     * <ul>
     * <li> Site admins </li>
     * <li> Project admins of projects that use Git </li>
     * <li> Members of custom ugroups that are Git admins in one or more project </li>
     * </ul>
     * <br/>
     * <br/>
     * The route returns:
     * <ul>
     * <li>Only unrestricted Gerrit servers when no option provided</li>
     * <li>All the project Gerrit servers available for the provided project</li>
     * </ul>
     *
     * @access hybrid
     *
     * @url GET
     *
     * @param string $for_project The project ID to search in {@from query} {@type int}
     *
     * @return array {@type Tuleap\Git\REST\v1\GerritServerRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function get($for_project = null)
    {
        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanListGerritServers($current_user);

        if ($for_project) {
            $project = $this->getProjectFromRequest($for_project);

            ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
                $current_user,
                $project
            );

            $servers = $this->server_factory->getAvailableServersForProject($project);
        } else {
            $servers = $this->server_factory->getUnrestrictedServers();
        }

        $representations = array();
        foreach ($servers as $server) {
            $representation = new GerritServerRepresentation();
            $representation->build($server);
            $representations[] = $representation;
        }

        $this->sendAllowHeaders();
        return array('servers' => $representations);
    }

    /**
     * @return \Project
     */
    private function getProjectFromRequest($project_id)
    {
        $project = $this->project_manager->getProject($project_id);

        if ($project->isError()) {
            throw new RestException(404, 'The provided project does not exist');
        }

        return $project;
    }

    private function checkUserCanListGerritServers(PFUser $user)
    {
        if (! $user->isSuperUser() && ! $this->server_permission_manager->isUserAllowedToListServers($user)) {
            throw new RestException(403, 'User is not allowed to list Gerrit server');
        }
    }

    /**
     * Return info about repository if exists
     *
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
