<?php
/**
 * This is the root of WebDAV virtual filesystem
 *
 * this class lists projects that the user is member of
 *
 * or all public projects in case the user is anonymous
 */
require_once ('WebDAVFRSProject.class.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');
require_once (dirname(__FILE__).'/../../../../src/common/dao/ProjectDao.class.php');

class WebDAVFRS extends Sabre_DAV_Directory {

    private $user;
    private $plugin;

    /**
     * Constructor of the class
     *
     * @param Plugin $plugin
     * @param User $user
     *
     * @return void
     */
    function __construct($plugin, $user) {

        $this->user = $user;
        $this->plugin = $plugin;

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
     * Returns a new WebDAVFRSProject from the given project id
     *
     * @param String $projectName
     *
     * @return WebDAVFRSProject
     *
     * @see lib/Sabre/DAV/Sabre_DAV_Directory#getChild($name)
     */
    function getChild($projectName) {

        $utils = WebDAVUtils::getInstance();
        $projectId = $utils->extractId($projectName);

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
        // he still have the right to access to all of them
        if (!$project->userCanRead()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized'));
        }

        // Check if the file release service is activated for the project
        if (!$project->usesFile()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_have_no_frs'));
        }

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

        return 'WebDAVFRSRoot';

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
     * @return User
     */
    function getUser() {

        return $this->user;

    }

    /**
     * Returns a new WebDAVFRSProject from the given group Id
     *
     * @param Integer $groupId
     *
     * @return WebDAVFRSProject
     */
    function getWebDAVProject($groupId) {

        $pm = ProjectManager::instance();
        return new WebDAVFRSProject($this->getUser(), $pm->getProject($groupId));

    }

    /**
     * Generate project list of the given user
     *
     * @param User $user
     *
     * @return Array
     */
    function getUserProjectList($user) {

        $res = $user->getProjects();
        $projects = array();
        foreach ($res as $groupId) {
            if ($this->isWebDAVAllowedForProject($groupId)) {
                $project = $this->getWebDAVProject($groupId);
                if ($project->usesFile()) {
                    $projects[] = $project;
                }
            }
        }
        return $projects;

    }

    /**
     * Generate public projects list
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
                    if ($project->usesFile()) {
                        $projects[] = $project;
                    }
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