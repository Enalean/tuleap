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

namespace Tuleap\ProFTPd;

use Backend;
use ProjectManager;

class SystemEventManager
{
    /** @var SystemEventManager */
    private $system_event_manager;

    /** @var Backend */
    private $backend;

    /** @var Admin\PermissionsManager */
    private $permissions_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var string */
    private $proftpd_base_directory;

    public function __construct(
        \SystemEventManager $system_event_manager,
        Backend $backend,
        Admin\PermissionsManager $permissions_manager,
        ProjectManager $project_manager,
        $proftpd_base_directory
    ) {
        $this->system_event_manager   = $system_event_manager;
        $this->backend                = $backend;
        $this->permissions_manager    = $permissions_manager;
        $this->project_manager        = $project_manager;
        $this->proftpd_base_directory = $proftpd_base_directory;
    }

    public function queueDirectoryCreate($project_name)
    {
        if (! is_dir($this->proftpd_base_directory . DIRECTORY_SEPARATOR . $project_name)) {
            $this->system_event_manager->createEvent(
                SystemEvent\PROFTPD_DIRECTORY_CREATE::NAME,
                $project_name,
                \SystemEvent::PRIORITY_HIGH,
                \SystemEvent::OWNER_ROOT
            );
        }
    }

    public function queueACLUpdate($project_name)
    {
        $this->system_event_manager->createEvent(
            SystemEvent\PROFTPD_UPDATE_ACL::NAME,
            $project_name,
            \SystemEvent::PRIORITY_HIGH,
            \SystemEvent::OWNER_ROOT
        );
    }

    public function getTypes()
    {
        return array(
            SystemEvent\PROFTPD_DIRECTORY_CREATE::NAME,
            SystemEvent\PROFTPD_UPDATE_ACL::NAME,
        );
    }

    public function instanciateEvents($type, &$dependencies)
    {
        switch ($type) {
            case \Tuleap\ProFTPd\SystemEvent\PROFTPD_DIRECTORY_CREATE::NAME:
                $dependencies = array(
                    $this->backend,
                    new Admin\ACLUpdater($this->backend),
                    $this->proftpd_base_directory
                );
                break;
            case \Tuleap\ProFTPd\SystemEvent\PROFTPD_UPDATE_ACL::NAME:
                $dependencies = array(
                    new Admin\ACLUpdater($this->backend),
                    $this->permissions_manager,
                    $this->project_manager,
                    $this->proftpd_base_directory
                );
                break;
            default:
                break;
        }
    }
}
