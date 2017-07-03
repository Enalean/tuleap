<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 */

namespace Tuleap\SVN\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\v1\FullRepositoryRepresentation;
use Tuleap\REST\v1\RepositoryRepresentationBuilder;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Dao;
use Tuleap\Svn\Repository\CannotFindRepositoryException;
use Tuleap\Svn\Repository\HookDao;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\SvnLogger;
use Tuleap\Svn\SvnPermissionManager;

class RepositoryResource extends AuthenticatedResource
{
    /**
     * @var RepositoryManager
     */
    public $repository_manager;

    /**
     * @var SvnPermissionManager
     */
    public $permission_manager;

    /**
     * @var \UserManager
     */
    public $user_manager;

    public function __construct()
    {
        $dao                      = new Dao();
        $logger                   = new SvnLogger();
        $system_command           = new \System_Command();
        $backend_svn              = \Backend::instance(\Backend::SVN);
        $this->repository_manager = new RepositoryManager(
            $dao,
            \ProjectManager::instance(),
            new SvnAdmin($system_command, $logger, $backend_svn),
            $logger,
            $system_command,
            new Destructor($dao, $logger),
            new HookDao(),
            \EventManager::instance(),
            $backend_svn,
            new AccessFileHistoryFactory(new AccessFileHistoryDao()),
            \SystemEventManager::instance()
        );

        $this->user_manager       = \UserManager::instance();
        $this->permission_manager = new SvnPermissionManager(
            new \User_ForgeUserGroupFactory(new \UserGroupDao()),
            \PermissionsManager::instance()
        );
    }

    /**
     * Return info about repository if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the repository
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get SVN
     *
     * Get info about project SVN repositories
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"project": {...},<br>
     *   &nbsp;"uri": "svn/90",<br>
     *   &nbsp;"name": "repo",<br>
     *   &nbsp;"settings": {<br>
     *   &nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;"mandatory_reference": true|false ,<br>
     *   &nbsp;&nbsp;"allow_commit_message_change": true|false<br>
     *   &nbsp;&nbsp;}<br>
     *   &nbsp;}<br>
     *  }<br>
     * </pre>
     *
     * @access hybrid
     *
     * @url GET {id}
     *
     * @param int $id Id of the repository
     *
     * @return FullRepositoryRepresentation
     *
     * @throws 404
     * @throws 403
     */
    public function get($id)
    {
        $this->checkAccess();

        try {
            $user       = $this->user_manager->getCurrentUser();
            $repository = $this->repository_manager->getRepositoryById($id);
            ProjectAuthorization::userCanAccessProject(
                $user,
                $repository->getProject(),
                new \URLVerification()
            );

            $this->sendAllowHeaders();

            $representation_builder = new RepositoryRepresentationBuilder(
                $this->permission_manager,
                $this->repository_manager
            );

            return $representation_builder->build($repository, $user);
        } catch (CannotFindRepositoryException $e) {
            throw new RestException('404', 'Repository not found');
        }
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
