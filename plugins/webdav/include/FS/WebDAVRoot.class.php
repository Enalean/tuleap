<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
class WebDAVRoot extends \Sabre\DAV\FS\Directory {

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
    public function __construct($plugin, $user, $maxFileSize)
    {
        $this->user        = $user;
        $this->plugin      = $plugin;
        $this->maxFileSize = $maxFileSize;

        parent::__construct('/');
    }

    /**
     * Generates the list of projects that user is member of
     * or all public projects in case the user is anonymous
     * don't generate those for which WebDAV plugin is not available
     *
     * @return array
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
     */
    function getChild($projectName) {

        $projectId = $this->getProjectIdByName($projectName);

        // Check for errors

        // Check if WebDAV plugin is activated for the project
        if (!$this->isWebDAVAllowedForProject($projectId)) {
            throw new \Sabre\DAV\Exception\Forbidden(
                $GLOBALS['Language']->getText('plugin_webdav_common', 'plugin_not_available')
            );
        }
        $project = $this->getWebDAVProject($projectId);

        // Check if project exists
        if (!$project->exist()) {
            throw new \Sabre\DAV\Exception\NotFound(
                $GLOBALS['Language']->getText('plugin_webdav_common', 'project_not_available')
            );
        }

        // Check if the project has the active status
        if (!$project->isActive()) {
            // Access denied error
            throw new \Sabre\DAV\Exception\Forbidden(
                $GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized')
            );
        }

        // Check if the user can access to the project
        // it's important to notice that even if in the listing the user don't see all public projects
        // she still have the right to access to all of them
        if (!$project->userCanRead()) {
            // Access denied error
            throw new \Sabre\DAV\Exception\Forbidden(
                $GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized')
            );
        }

        return $project;

    }

    /**
     * This  method is used just to suit the class Sabre_DAV_INode
     *
     * @return String
     */
    function getName() {

        return ' WebDAV Root';

    }

    /**
     * This is used only to suit the class Sabre_DAV_Node
     *
     * @return void
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
     * @return array
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
     * @return array
     */
    function getPublicProjectList() {

        $dao = new ProjectDao(CodendiDataAccess::instance());
        $res = $dao->searchByPublicStatus(true);
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
