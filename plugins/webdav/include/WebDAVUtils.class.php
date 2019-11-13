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

use Tuleap\FRS\FRSPermissionManager;

/**
 * This class contains methods used in WebDAV plugin
 */
class WebDAVUtils
{

    protected static $instance;

    /**
     * Instance of docman plugin
     *
     * @var DocmanPlugin
     */
    protected $docmanPlugin;

    /**
     * We don't permit an explicit call of the constructor! (like $utils = new WebDAVUtils())
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * We don't permit cloning the singleton (like $webdavutils = clone $utils)
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Returns the instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public static function getInstance()
    {

        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setInstance(WebDAVUtils $instance)
    {
        self::$instance = $instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

   /**
     * Replaces '/', '%' and '|' by their respective ASCII code
     *
     * @param String $name
     *
     * @return String
     */
    function convertName($name)
    {

        $name = str_replace('%', '%25', $name);
        $name = str_replace('/', '%2F', $name);
        $name = str_replace('|', '&#124;', $name);
        return $name;
    }

    /**
     * Retrieves the converted HTML special characters
     *
     * @param String $name
     *
     * @return String
     */
    function unconvertHTMLSpecialChars($name)
    {
        return util_unconvert_htmlspecialchars($this->convertName($name));
    }

    /**
     * Replaces ASCII codes of '/', '%' and '|' by the respective characters
     *
     * @param String $name
     *
     * @return String
     */
    function retrieveName($name)
    {

        $name = str_replace('%2F', '/', $name);
        $name = str_replace('%25', '%', $name);
        $name = str_replace('&#124;', '|', $name);
        return $name;
    }

    /**
     * For test purpose
     */
    protected function getFRSPermissionManager()
    {
        return FRSPermissionManager::build();
    }

    /**
     * Tests if the user is Superuser, project admin or File release admin
     *
     * @param PFUser $user
     * @param int $groupId
     *
     * @return bool
     */
    function userIsAdmin($user, $project_id)
    {
        $permission_manager = $this->getFRSPermissionManager();
        $project = $this->getProjectManager()->getProject($project_id);

        return ($user->isSuperUser() || $permission_manager->isAdmin($project, $user));
    }

    /**
     * Tests if the user is Superuser, or File release admin
     *
     * @param PFUser $user
     * @param int $groupId
     *
     * @return bool
     */
    function userCanWrite($user, $project_id)
    {
        $permission_manager = $this->getFRSPermissionManager();
        $project = $this->getProjectManager()->getProject($project_id);
        return $this->isWriteEnabled() && ($user->isSuperUser() || $permission_manager->isAdmin($project, $user));
    }

    /**
     * Returns an instance of ProjectManager
     *
     * @return ProjectManager
     */
    public function getProjectManager()
    {

        $pm = ProjectManager::instance();
        return $pm;
    }

    /**
     * Returns a FRSPackageFactory
     *
     * @return FRSPackageFactory
     */
    function getPackageFactory()
    {

        return new FRSPackageFactory();
    }

    /**
     * Returns a FRSReleaseFactory
     *
     * @return FRSReleaseFactory
     */
    function getReleaseFactory()
    {

        return new FRSReleaseFactory();
    }

    /**
     * Returns a FRSFileFactory
     *
     * @return FRSFileFactory
     */
    function getFileFactory()
    {

        return new FRSFileFactory();
    }

    /**
     * Returns a PermissionsManager instance
     *
     * @return PermissionsManager
     */
    function getPermissionsManager()
    {

        $pm = & PermissionsManager::instance();
        return $pm;
    }

    /**
     * Returns event manager instance
     *
     * @return EventManager
     */
    function getEventManager()
    {
        return EventManager::instance();
    }

    function getIncomingFileSize($name)
    {
        return PHP_BigFile::getSize($GLOBALS['ftp_incoming_dir'].'/'.$name);
    }

    function getIncomingFileMd5Sum($file)
    {
        return PHP_BigFile::getMd5Sum($file);
    }

    /**
     * Returns an instance of PermissionsManager
     *
     * @param Project $project Used project
     *
     * @return Docman_PermissionsManager
     */
    function getDocmanPermissionsManager($project)
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    /**
     * Returns a new instance of ItemFactory
     *
     * @return Docman_ItemFactory
     */
    function getDocmanItemFactory()
    {
        return new Docman_ItemFactory();
    }

    /**
     * Returns a new instance of VersionFactory
     *
     * @return Docman_VersionFactory
     */
    function getVersionFactory()
    {
        return new Docman_VersionFactory();
    }

    /**
     * Returns the file system root of docman
     *
     * @return String
     */
    function getDocmanRoot()
    {
        $pluginManager = PluginManager::instance();
        $p             = $pluginManager->getPluginByName('docman');
        $info          = $p->getPluginInfo();
        return $info->getPropertyValueForName('docman_root');
    }

    /**
     * Returns a new instance of FileStorage
     *
     * @return Docman_FileStorage
     */
    function getFileStorage()
    {
        return new Docman_FileStorage($this->getDocmanRoot());
    }

    /**
     * Tells if write acces is enabled or not for the WebDAV plugin
     *
     * @return bool
     */
    function isWriteEnabled()
    {
        $pluginManager = PluginManager::instance();
        $p             = $pluginManager->getPluginByName('webdav');
        $info          = $p->getPluginInfo();
        return $info->getPropertyValueForName('write_access_enabled');
    }

    /**
     * Use Docman MVC model to perform webdav actions
     *
     * @param WebDAV_Request $request
     */
    function processDocmanRequest(WebDAV_Request $request)
    {
        if (!$this->docmanPlugin) {
            $pluginMgr = PluginManager::instance();
            $this->docmanPlugin = $pluginMgr->getPluginByName('docman');
            if (!$this->docmanPlugin || ($this->docmanPlugin && !$pluginMgr->isPluginAvailable($this->docmanPlugin))) {
                throw new WebDAVExceptionServerError($GLOBALS['Language']->getText('plugin_webdav_common', 'plugin_not_available'));
            }
        }
        $GLOBALS['Response'] = new WebDAV_Response();
        $controller = new WebDAV_DocmanController($this->docmanPlugin, $request);
        $controller->process();

        if ($GLOBALS['Response']->feedbackHasErrors()) {
            //file_put_contents('/tmp/webdav.log', $GLOBALS['Response']->getRawFeedback());
            throw new WebDAVExceptionServerError($GLOBALS['Response']->getRawFeedback());
        }
    }
}
