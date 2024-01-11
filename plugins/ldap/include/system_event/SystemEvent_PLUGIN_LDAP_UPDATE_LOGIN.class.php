<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\SVNCore\Event\UpdateProjectAccessFilesEvent;

/**
 * Manage rename of LDAP users in the whole platform.
 *
 * As of today, when LDAP authentication is in use, the LDAP login
 * is used for web authentication (sic!) dans for Subversion authentication.
 *
 * So we need to propagate LDAP login change to SVNAccessFile only (the Tuleap
 * user name is not changed).
 */
class SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN extends SystemEvent
{
    /** @var UserManager */
    private $user_manager;

    /** @var BackendSVN */
    private $backend_svn;

    /** @var ProjectManager */
    private $project_manager;

    /** @var LDAP_ProjectManager */
    private $ldap_project_manager;

    public function injectDependencies(
        UserManager $user_manager,
        BackendSVN $backend_svn,
        ProjectManager $project_manager,
        LDAP_ProjectManager $ldap_project_manager,
    ) {
        $this->user_manager         = $user_manager;
        $this->backend_svn          = $backend_svn;
        $this->project_manager      = $project_manager;
        $this->ldap_project_manager = $ldap_project_manager;
    }

    /**
     * Execute action
     *
     * @see src/common/system_event/SystemEvent::process()
     */
    public function process()
    {
        $user_ids    = $this->getParametersAsArray();
        $project_ids = [];

        // Get all projects the user is member of (project member or user group member)
        $um = $this->getUserManager();
        foreach ($user_ids as $user_id) {
            $user = $um->getUserById($user_id);
            if ($user && ($user->isActive() || $user->isRestricted())) {
                $prjs = $user->getAllProjects();
                foreach ($prjs as $pid) {
                    if (! isset($project_ids[$pid])) {
                        $project_ids[$pid] = $pid;
                    }
                }
            }
        }

        // Update SVNAccessFile of projects
        $event_manager = EventManager::instance();
        foreach ($project_ids as $project_id) {
            $project = $this->project_manager->getProject($project_id);
            $event_manager->dispatch(new UpdateProjectAccessFilesEvent($project));
        }

        $this->done();
    }

    /**
     * Display parameters
     *
     * @see src/common/system_event/SystemEvent::verbalizeParameters()
     *
     * @param bool $with_link With link
     */
    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }

    /**
     * Wrapper for UserManager
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->user_manager;
    }
}
