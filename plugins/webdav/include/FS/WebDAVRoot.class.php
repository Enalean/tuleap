<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once ('WebDAVProject.class.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');
require_once ('common/dao/ProjectDao.class.php');

/**
 * This is the root of WebDAV virtual filesystem
 *
 * this class lists projects that the user is member of
 *
 * or all public projects in case the user is anonymous
 */
class WebDAVRoot extends Sabre_DAV_Directory {

    private $user;
    private $plugin;
    private $maxFileSize;

    /**
     * Constructor of the class
     *
     * @param Plugin $plugin
     * @param PFUser $user
     * @param Integer $maxFileSize
     *
     * @return void
     */
    function __construct($plugin, $user, $maxFileSize) {

        $this->user = $user;
        $this->plugin = $plugin;
        $this->maxFileSize = $maxFileSize;

    }

    /**
     * Generates the list of projects that user is member of
     * or all public projects in case the user is anonymous
     * don't generate those for which WebDAV plugin is not available
     *
     * @return Array
     *
     * @see lib/Sabre/DAV/Sabre_DAV_IDirectory#getChildren()
     */
    function getChildren() {

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
    function getChild($projectName) {

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
    function getName() {

        return ' WebDAV Root';

    }

    /**
     * This is used only to suit the class Sabre_DAV_Node
     *
     * @return NULL
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    function getLastModified() {

        return;

    }

    /**
     * Returns the User
     *
     * @return PFUser
     */
    function getUser() {

        return $this->user;

    }

    /**
     * Returns the max file size
     *
     * @return Integer
     */
    function getMaxFileSize() {
        return $this->maxFileSize;
    }

    /**
     * Returns a project from its name
     *
     * @param String $projectName
     *
     * @return Integer
     */
    function getProjectIdByName($projectName) {

        $dao = new ProjectDao(CodendiDataAccess::instance());
        $res=$dao->searchByUnixGroupName($projectName);
        $groupId=$res->getRow();
        return $groupId['group_id'];

    }

    /**
     * Returns a new WebDAVProject from the given group Id
     *
     * @param Integer $groupId
     *
     * @return WebDAVProject
     */
    function getWebDAVProject($groupId) {

        $utils = WebDAVUtils::getInstance();
        $project = $utils->getProjectManager()->getProject($groupId);
        return new WebDAVProject($this->getUser(), $project, $this->getMaxFileSize());

    }

    /**
     * Generates project list of the given user
     *
     * @param PFUser $user
     *
     * @return Array
     */
    function getUserProjectList($user) {

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
     * @return Array
     */
    function getPublicProjectList() {

        $dao = new ProjectDao(CodendiDataAccess::instance());
        $res = $dao->searchByPublicStatus(Project::IS_PUBLIC);
        $projects = array();
        if ($res && !$res->isError() && $res->rowCount() > 0) {
            foreach ($res as $row) {
                if ($this->isWebDAVAllowedForProject($row['group_id'])) {
                    $project = $this->getWebDAVProject($row['group_id']);
                    $projects[] = $project;
                }
            }
        }
        return $projects;

    }

    /**
     * Checks whether the WebDAV plugin is available for the project or not
     *
     * @param Integer $groupId
     *
     * @return Boolean
     */
    function isWebDAVAllowedForProject($groupId) {

        return PluginManager::instance()->isPluginAllowedForProject($this->plugin, $groupId);

    }

}

?>