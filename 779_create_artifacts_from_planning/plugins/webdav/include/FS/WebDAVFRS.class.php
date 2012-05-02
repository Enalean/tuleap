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

require_once ('WebDAVFRSPackage.class.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');

/**
 * This class lists the packages of a given project
 *
 * It is an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */
class WebDAVFRS extends Sabre_DAV_Directory {

    private $user;
    private $project;
    private $maxFileSize;

    /**
     * Constuctor of the class
     *
     * @param User $user
     * @param Project $project
     * @param Integer $maxFileSize
     *
     * @return void
     */
    function __construct($user, $project, $maxFileSize) {

        $this->user = $user;
        $this->project = $project;
        $this->maxFileSize = $maxFileSize;

    }

    /**
     * Generates the list of packages under the project
     *
     * @return array
     */
    function getChildren() {

        $children = array();

        // Generate package list of the given project
        $packages = $this->getPackageList($this->getProject());
        // Loop through the packages, and create objects for each node
        foreach ($packages as $pkg) {
            $package = $this->getWebDAVPackage($pkg);
            if ($package->userCanRead($this->getUser())) {
                $children[] = $package;
            }
        }
        return $children;

    }

    /**
     * Returns the given package
     *
     * @param String $packageName
     *
     * @return WebDAVFRSPackage
     *
     * @see lib/Sabre/DAV/Sabre_DAV_Directory#getChild($name)
     */
    function getChild($packageName) {

        $packageName = $this->getUtils()->retrieveName($packageName);
        $package = $this->getWebDAVPackage($this->getFRSPackageFromName($packageName));

        // Check for errors

        // Check if Package exists
        if (!$package->exist()) {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'package_not_available'));
        }

        if (!$package->userCanRead($this->getUser())) {

            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_access_not_authorized'));

        }

        return $package;

    }

    /**
     * Returns the name of the service
     *
     * @return String
     *
     * @see lib/Sabre/DAV/Sabre_DAV_INode#getName()
     */
    function getName() {
        return $GLOBALS['Language']->getText('plugin_webdav_common', 'files');
    }

    /**
     * FRS don't have a last modified date this
     * is used only to suit the class Sabre_DAV_Node
     *
     * @return NULL
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    function getLastModified() {

        return;

    }

    /**
     * Returns the project
     *
     * @return FRSProject
     */
    function getProject() {

        return $this->project;

    }

    /**
     * Returns the project Id
     *
     * @return Integer
     */
    function getGroupId() {

        return $this->getProject()->getGroupId();

    }

    /**
     * Returns the user
     *
     * @return User
     */
    function getUser() {

        return $this->user;

    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils() {

        return WebDAVUtils::getInstance();

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
     * Returns a new FRSPackage from its name
     *
     * @param String $packageName
     *
     * @return FRSPackage
     */
    function getFRSPackageFromName($packageName) {

        $utils = $this->getUtils();
        return $utils->getPackageFactory()->getFRSPackageFromDb($utils->getPackageFactory()->getPackageIdByName($packageName, $this->getGroupId()), $this->getGroupId());

    }

    /**
     * Returns a new WebDAVFRSPackage from the given FRSPackage
     *
     * @param FRSPackage $package
     *
     * @return WebDAVFRSPackage
     */
    function getWebDAVPackage($package) {

        return new WebDAVFRSPackage($this->getUser(), $this->getProject(), $package, $this->getMaxFileSize());

    }

    /**
     * Generates package list of the given GroupId
     *
     * @param Integer $groupId
     *
     * @return Array
     */
    function getPackageList($project) {

        $utils = $this->getUtils();
        return $utils->getPackageFactory()->getFRSPackagesFromDb($project->getGroupId());

    }

    /**
     * Checks whether the user can read the project or not
     *
     * @return Boolean
     */
    function userCanRead() {

        return ($this->getProject()->userIsMember()
        || ($this->getProject()->isPublic() && !$this->getUser()->isRestricted()));

    }

    /**
     * Tests if the user is Superuser or File release admin
     *
     * @return Boolean
     */
    function userCanWrite() {
        $utils = $this->getUtils();
        return $utils->userCanWrite($this->getUser(), $this->getGroupId());
    }

    /**
     * Creates a new package
     *
     * @param String $name
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Directory#createDirectory($name)
     */
    function createDirectory($name) {

        if ($this->userCanWrite()) {
            $utils = $this->getUtils();
            if (!$utils->getPackageFactory()->isPackageNameExist($name, $this->getGroupId())) {
                $packageData['name'] = htmlspecialchars($name);
                $packageData['group_id'] = $this->getGroupId();
                $packageData['status_id'] = 1;
                $packageId = $utils->getPackageFactory()->create($packageData);
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'package_name_exist'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_denied_create'));
        }

    }

}

?>