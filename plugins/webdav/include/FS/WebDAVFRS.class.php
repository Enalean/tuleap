<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\FRS\FRSPermissionManager;

/**
 * This class lists the packages of a given project
 *
 * It is an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */
class WebDAVFRS extends Sabre_DAV_Directory
{

    private $user;
    private $project;
    private $maxFileSize;

    /**
     * Constuctor of the class
     *
     * @param PFUser $user
     * @param Project $project
     * @param int $maxFileSize
     *
     * @return void
     */
    public function __construct($user, $project, $maxFileSize)
    {
        $this->user = $user;
        $this->project = $project;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Generates the list of packages under the project
     *
     * @return array
     */
    public function getChildren()
    {
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
    public function getChild($packageName)
    {
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
    public function getName()
    {
        return $GLOBALS['Language']->getText('plugin_webdav_common', 'files');
    }

    /**
     * FRS don't have a last modified date this
     * is used only to suit the class Sabre_DAV_Node
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    public function getLastModified()
    {
        return 0;
    }

    /**
     * Returns the project
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Returns the project Id
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->getProject()->getGroupId();
    }

    /**
     * Returns the user
     *
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public function getUtils()
    {
        return WebDAVUtils::getInstance();
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
     * Returns a new FRSPackage from its name
     *
     * @param String $packageName
     *
     * @return FRSPackage
     */
    public function getFRSPackageFromName($packageName)
    {
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
    public function getWebDAVPackage($package)
    {
        return new WebDAVFRSPackage($this->getUser(), $this->getProject(), $package, $this->getMaxFileSize());
    }

    /**
     * Generates package list of the given GroupId
     *
     * @param Project $project
     *
     * @return Array
     */
    public function getPackageList($project)
    {
        $utils = $this->getUtils();
        return $utils->getPackageFactory()->getFRSPackagesFromDb($project->getGroupId());
    }

    /** @protected for testing purpose */
    protected function getPermissionsManager()
    {
        return FRSPermissionManager::build();
    }

    /**
     * Checks whether the user can read the project or not
     *
     * @return bool
     */
    public function userCanRead()
    {
        return $this->getPermissionsManager()->userCanRead($this->getProject(), $this->getUser());
    }

    /**
     * Tests if the user is Superuser or File release admin
     *
     * @return bool
     */
    public function userCanWrite()
    {
        return $this->isWriteEnabledByPlugin() &&
               $this->getPermissionsManager()->isAdmin($this->getProject(), $this->getUser());
    }

    private function isWriteEnabledByPlugin()
    {
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
    public function createDirectory($name)
    {
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
