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

/**
 * This class contains methods used in WebDAV plugin
 */
class WebDAVUtils {

    protected static $instance;

    /**
     * We don't permit an explicit call of the constructor! (like $utils = new WebDAVUtils())
     *
     * @return void
     */
    private function __construct() {
    }

    /**
     * We don't permit cloning the singleton (like $webdavutils = clone $utils)
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Returns the instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public static function getInstance() {

        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;

    }

    /**
     * Retuns if the filename is valid or not
     *
     * @param String $name
     *
     * @return Boolean
     */
    function isValidFileName($name) {

        return util_is_valid_filename($name);

    }

    /**
     * Replaces '/', '%' and '|' by their respective ASCII code
     *
     * @param String $name
     *
     * @return String
     */
    function convertName($name) {

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
    function unconvertHTMLSpecialChars($name) {
        return util_unconvert_htmlspecialchars($this->convertName($name));
    }

    /**
     * Replaces ASCII codes of '/', '%' and '|' by the respective characters
     *
     * @param String $name
     *
     * @return String
     */
    function retrieveName($name) {

        $name = str_replace('%2F', '/', $name);
        $name = str_replace('%25', '%', $name);
        $name = str_replace('&#124;', '|', $name);
        return $name;

    }

    /**
     * Tests if the user is Superuser, project admin or File release admin
     *
     * @param User $user
     * @param Integer $groupId
     *
     * @return Boolean
     */
    function userIsAdmin($user, $groupId) {

        // A refers to admin
        // R2 refers to File release admin
        return ($user->isSuperUser() || $user->isMember($groupId, 'A') || $user->isMember($groupId, 'R2'));

    }

    /**
     * Tests if the user is Superuser, or File release admin
     *
     * @param User $user
     * @param Integer $groupId
     *
     * @return Boolean
     */
    function userCanWrite($user, $groupId) {

        // R2 refers to File release admin
        return ($user->isSuperUser() || $user->isMember($groupId, 'R2'));

    }

    /**
     * Returns an instance of ProjectManager
     *
     * @return FRSProjectManager
     */
    function getProjectManager() {

        $pm = ProjectManager::instance();
        return $pm;

    }

    /**
     * Returns a FRSPackageFactory
     *
     * @return FRSPackageFactory
     */
    function getPackageFactory() {

        return new FRSPackageFactory();

    }

    /**
     * Returns a FRSReleaseFactory
     *
     * @return FRSReleaseFactory
     */
    function getReleaseFactory() {

        return new FRSReleaseFactory();

    }

    /**
     * Returns a FRSFileFactory
     *
     * @return FRSFileFactory
     */
    function getFileFactory() {

        return new FRSFileFactory();

    }

    /**
     * Returns a PermissionsManager instance
     *
     * @return PermissionsManager
     */
    function getPermissionsManager() {

        $pm = & PermissionsManager::instance();
        return $pm;

    }

    function getIncomingFileSize($name) {
        return file_utils_get_size($GLOBALS['ftp_incoming_dir'].'/'.$name);
    }

    function getIncomingFileMd5Sum($file) {
        return PHP_BigFile::getMd5Sum($file);
    }

    /**
     * Returns an instance of PermissionsManager
     *
     * @param Project $project Used project
     *
     * @return Docman_PermissionsManager
     */
    function getDocmanPermissionsManager($project) {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    /**
     * Returns a new instance of VersionFactory
     *
     * @return Docman_VersionFactory
     */
    function getVersionFactory() {
        return new Docman_VersionFactory();
    }

    /**
     * Returns a new instance of FileStorage
     *
     * @return Docman_FileStorage
     */
    function getFileStorage() {
        $pluginManager = PluginManager::instance();
        $p             = $pluginManager->getPluginByName('docman');
        $info          = $p->getPluginInfo();
        return new Docman_FileStorage($info->getPropertyValueForName('docman_root'));
    }

    /**
     * Tells if write acces is enabled or not for the WebDAV plugin
     *
     * @return Boolean
     */
    function isWriteEnabled() {
        $pluginManager = PluginManager::instance();
        $p             = $pluginManager->getPluginByName('webdav');
        $info          = $p->getPluginInfo();
        return $info->getPropertyValueForName('write_access_enabled');
    }

}

?>