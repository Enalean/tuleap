<?php
/**
 * Copyright Enalean (c) 2012-Present. All rights reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

/**
 * This is the root of WebDAV virtual filesystem
 *
 * this class lists projects that the user is member of
 *
 * or all public projects in case the user is anonymous
 */
class WebDAVRoot extends Sabre_DAV_Directory
{

    private $user;
    private $plugin;
    private $maxFileSize;

    /**
     * @var ProjectDao
     */
    private $project_dao;

    /**
     * Constructor of the class
     *
     * @param Plugin $plugin
     * @param PFUser $user
     * @param int $maxFileSize
     *
     * @return void
     */
    public function __construct($plugin, $user, $maxFileSize, ProjectDao $project_dao)
    {
        $this->user = $user;
        $this->plugin = $plugin;
        $this->maxFileSize = $maxFileSize;
        $this->project_dao = $project_dao;
    }

    /**
     * Generates the list of projects that user is member of
     * or all public projects in case the user is anonymous
     * don't generate those for which WebDAV plugin is not available
     *
     * @return Sabre_DAV_INode[]
     *
     * @see lib/Sabre/DAV/Sabre_DAV_IDirectory#getChildren()
     */
    public function getChildren()
    {
        if ($this->getUser()->isAnonymous()) {
            // Generate public project list
            return $this->getPublicProjectList();
        } else {
            // Generate project list for the given user
            return $this->getUserProjectList($this->getUser());
        }
    }

    /**
     * Returns a new WebDAVProject from the given project id
     *
     * @param String $projectName
     *
     * @return WebDAVProject
     *
     * @see lib/Sabre/DAV/Sabre_DAV_Directory#getChild($name)
     */
    public function getChild($projectName)
    {
        $projectId = $this->getProjectIdByName($projectName);

        // Check for errors

        // Check if WebDAV plugin is activated for the project
        if (!$this->isWebDAVAllowedForProject($projectId)) {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'plugin_not_available'));
        }
        $project = $this->getWebDAVProject($projectId);

        // Check if project exists
        if (!$project->exist()) {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'project_not_available'));
        }

        // Check if the project has the active status
        if (!$project->isActive()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized'));
        }

        // Check if the user can access to the project
        // it's important to notice that even if in the listing the user don't see all public projects
        // she still have the right to access to all of them
        if (!$project->userCanRead()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized'));
        }

        // Check if the file release service is activated for the project
        /*if (!$project->usesFile()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_have_no_frs'));
        }*/

        return $project;
    }

    /**
     * This  method is used just to suit the class Sabre_DAV_INode
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_INode#getName()
     *
     * @return String
     */
    public function getName()
    {
        return ' WebDAV Root';
    }

    /**
     * This is used only to suit the class Sabre_DAV_Node
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    public function getLastModified()
    {
        return 0;
    }

    /**
     * Returns the User
     *
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns the max file size
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Returns a project from its name
     *
     * @param String $projectName
     *
     * @return int
     */
    public function getProjectIdByName($projectName)
    {
        $res = $this->project_dao->searchByUnixGroupName($projectName);
        $groupId = $res->getRow();
        return $groupId['group_id'];
    }

    /**
     * Returns a new WebDAVProject from the given group Id
     *
     * @param int $groupId
     *
     * @return WebDAVProject
     */
    public function getWebDAVProject($groupId)
    {
        return new WebDAVProject(
            $this->getUser(),
            WebDAVUtils::getInstance()->getProjectManager()->getProject($groupId),
            $this->getMaxFileSize(),
            new ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );
    }

    /**
     * Generates project list of the given user
     *
     * @param PFUser $user
     *
     * @return array
     */
    public function getUserProjectList($user): array
    {
        $res = $user->getProjects();
        $projects = array();
        foreach ($res as $groupId) {
            if ($this->isWebDAVAllowedForProject($groupId)) {
                $project = $this->getWebDAVProject($groupId);
                if ($project->userCanRead()) {
                    $projects[] = $project;
                }
            }
        }
        return $projects;
    }

    /**
     * Generates public projects list
     *
     * @return array
     */
    public function getPublicProjectList(): array
    {
        $projects = [];
        foreach ($this->project_dao->searchByPublicStatus(true) as $row) {
            if ($this->isWebDAVAllowedForProject($row['group_id'])) {
                $project = $this->getWebDAVProject($row['group_id']);
                $projects[] = $project;
            }
        }
        return $projects;
    }

    /**
     * Checks whether the WebDAV plugin is available for the project or not
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function isWebDAVAllowedForProject($groupId)
    {
        return PluginManager::instance()->isPluginAllowedForProject($this->plugin, $groupId);
    }
}
