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

use Tuleap\ProFTPd\Admin\ACLUpdater;
use RuntimeException;
use Backend;

class PROFTPD_DIRECTORY_CREATE extends \SystemEvent
{
    public const NAME = 'Tuleap\ProFTPd\SystemEvent\PROFTPD_DIRECTORY_CREATE';

    /** @var Backend */
    private $backend;

    /** @var ACLUpdater */
    private $acl_updater;

    /** @var string */
    private $ftp_directory;

    public function injectDependencies(Backend $backend, ACLUpdater $acl_updater, $ftp_directory)
    {
        $this->backend       = $backend;
        $this->acl_updater   = $acl_updater;
        $this->ftp_directory = $ftp_directory;
    }

    public function process()
    {
        $project_name    = $this->getProjectName();
        $repository_path = $this->makeRepository($this->ftp_directory, $project_name);
        $this->setPermissions($repository_path, $project_name);
        $this->setfacl($repository_path);

        $this->done();
    }

    private function getProjectName()
    {
        $project_name = $this->getParameter(0);
        if ($project_name) {
            return $project_name;
        }
        throw new RuntimeException('Impossible to get a valid project name');
    }

    private function makeRepository($ftp_directory, $group_name)
    {
        $new_repository_path = $ftp_directory . DIRECTORY_SEPARATOR . $group_name;
        if (! file_exists($new_repository_path)) {
            if (! mkdir($new_repository_path)) {
                throw new RuntimeException("Cannot create directory $new_repository_path");
            }
        }
        return $new_repository_path;
    }

    private function setPermissions($path, $group_name)
    {
        $this->backend->changeOwnerGroupMode($path, "dummy", $group_name, 00700);
    }

    private function setfacl($path)
    {
        $this->acl_updater->recursivelyApplyACL($path, \ForgeConfig::get('sys_http_user'), '', '');
    }

    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }
}
