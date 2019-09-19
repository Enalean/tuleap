<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\SystemEvent;

use Backend;
use RuntimeException;
use Tuleap\ProFTPd\Admin;
use ProjectManager;
use Project;

class PROFTPD_UPDATE_ACL extends \SystemEvent
{
    public const NAME = 'Tuleap\ProFTPd\SystemEvent\PROFTPD_UPDATE_ACL';

    /** @var Admin\ACLUpdater */
    private $acl_updater;

    /** @var string */
    private $ftp_directory;

    /** @var PermissionsManager */
    private $permissions_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function injectDependencies(Admin\ACLUpdater $acl_updater, Admin\PermissionsManager $permissions_manager, ProjectManager $project_manager, $ftp_directory)
    {
        $this->acl_updater             = $acl_updater;
        $this->ftp_directory       = $ftp_directory;
        $this->permissions_manager = $permissions_manager;
        $this->project_manager     = $project_manager;
    }

    public function process()
    {
        $project     = $this->getProjectFromParameters();
        $this->acl_updater->recursivelyApplyACL(
            $this->getDirectoryPath($project),
            $GLOBALS['sys_http_user'],
            $this->getWriters($project),
            $this->getReaders($project)
        );
        $this->done();
    }

    private function getWriters(Project $project)
    {
        return $this->permissions_manager->getUGroupSystemNameFor($project, Admin\PermissionsManager::PERM_WRITE);
    }

    private function getReaders(Project $project)
    {
        return $this->permissions_manager->getUGroupSystemNameFor($project, Admin\PermissionsManager::PERM_READ);
    }

    private function getDirectoryPath(Project $project)
    {
        return realpath($this->ftp_directory . DIRECTORY_SEPARATOR . $project->getUnixName());
    }

    private function getProjectFromParameters()
    {
        $project = $this->project_manager->getProjectByUnixName($this->getParameter(0));
        if ($project && ! $project->isError()) {
            return $project;
        }
        throw new RuntimeException('Impossible to get a valid project name');
    }

    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }
}
