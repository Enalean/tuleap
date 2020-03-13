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

/**
 * This class is used to mount the releases of a given
 * package into the WebDAV virtual file system.
 *
 * It's an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */
class WebDAVFRSPackage extends Sabre_DAV_Directory
{

    private $user;
    private $project;
    private $package;
    private $maxFileSize;

    /**
     * Constructor of the class
     *
     * @param PFUser $user
     * @param Project $project
     * @param FRSPackage $package
     * @param int $maxFileSize
     *
     * @return void
     */
    public function __construct($user, $project, $package, $maxFileSize)
    {
        $this->user = $user;
        $this->project = $project;
        $this->package = $package;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Generates the list of releases under the package
     *
     * @return Sabre_DAV_INode[]
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_IDirectory#getChildren()
     */
    public function getChildren()
    {
        $children = array();

        // Generate release list of the given package
        $releases = $this->getReleaseList($this->getPackage());
        // Loop through the releases, and create objects for each node
        foreach ($releases as $rls) {
            $release = $this->getWebDAVRelease($rls);
            if ($release->userCanRead($this->getUser())) {
                $children[] = $release;
            }
        }
        return $children;
    }

    /**
     * Returns the given release
     *
     * @param String $releaseName
     *
     * @return WebDAVFRSRelease
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Directory#getChild($name)
     */
    public function getChild($releaseName)
    {
        $releaseName = $this->getUtils()->retrieveName($releaseName);
        $frs_release = $this->getFRSReleaseFromName($releaseName);
        if ($frs_release === null) {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'release_not_available'));
        }
        $release = $this->getWebDAVRelease($frs_release);

        // Check for errors

        // Check if Release exists
        if (!$release->exist()) {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'release_not_available'));
        }

        if (!$release->userCanRead($this->getUser())) {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_access_not_authorized'));
        }

        return $release;
    }

    /**
     * Returns the name of the package
     *
     * @return String
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_INode#getName()
     */
    public function getName()
    {
        /* To keep the Url correct the slashes "/" in the name
         *  of the package were replaced by its ascii code "%2F"
         *  same for the "%" replaced by "%25"  */
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getPackage()->getName());
    }

    /**
     * Packages don't have a last modified date this
     * is used only suit the class Sabre_DAV_Node
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    public function getLastModified()
    {
        return 0;
    }

    /**
     * Returns the package as an object instance of FRSPackage
     *
     * @return FRSPackage
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Returns the package Id
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->getPackage()->getPackageID();
    }

    /**
     * Returns the project that package belongs to
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
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
     * Returns a new FRSRelease from its name
     *
     * @param String $releaseName
     *
     */
    public function getFRSReleaseFromName($releaseName): ?FRSRelease
    {
        $utils = $this->getUtils();
        return $utils->getReleaseFactory()->getFRSReleaseFromDb($utils->getReleaseFactory()->getReleaseIdByName($releaseName, $this->getPackageId()), $this->getProject()->getGroupId(), $this->getPackageId());
    }

    /**
     * Returns a new WebDAVFRSRelease from the given release
     *
     * @param FRSrelease $release
     *
     * @return WebDAVFRSRelease
     */
    public function getWebDAVRelease($release)
    {
        return new WebDAVFRSRelease($this->getUser(), $this->getProject(), $this->getPackage(), $release, $this->getMaxFileSize());
    }

    /**
     * Generates release list of the given package
     *
     * @param FRSPackage $package
     *
     * @return array
     */
    public function getReleaseList($package): array
    {
        $utils = $this->getUtils();
        return $utils->getReleaseFactory()->getFRSReleasesFromDb($package->getPackageId());
    }

    /**
     * Returns whether the package exists or not
     *
     * @return bool
     */
    public function exist()
    {
        return($this->getPackage() && !$this->getPackage()->isDeleted());
    }

    /**
     * Checks whether the user can read the package or not
     *
     * @param PFUser $user
     *
     * @return bool
     */
    public function userCanRead($user)
    {
        return (($this->getPackage()->isActive() && $this->getPackage()->userCanRead($user->getId()))
            || ($this->getPackage()->isHidden() && $this->userIsAdmin()));
    }

    /**
     * Returns if the user is superuser, project admin or File release admin
     *
     * @return bool
     */
    public function userIsAdmin()
    {
        $utils = $this->getUtils();
        return $utils->userIsAdmin($this->getUser(), $this->getProject()->getGroupId());
    }

    /**
     * Returns if the user is Superuser or File release admin
     *
     * @return bool
     */
    public function userCanWrite()
    {
        $utils = $this->getUtils();
        return $utils->userCanWrite($this->getUser(), $this->getProject()->getGroupId());
    }

    /**
     * Deletes the package
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#delete()
     */
    public function delete()
    {
        if ($this->userCanWrite()) {
            // don't delete a package if it is not empty
            $releases = $this->getReleaseList($this->getPackage());
            $numReleases = count($releases);
            if ($numReleases > 0) {
                throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_not_empty'));
            } else {
                $utils = $this->getUtils();
                $result = $utils->getPackageFactory()->delete_package($this->getProject()->getGroupId(), $this->getPackageId());
                if ($result == 0) {
                    throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_not_available'));
                }
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_denied_delete'));
        }
    }

    /**
     * Renames the package
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#setName($name)
     */
    public function setName($name)
    {
        $utils = $this->getUtils();
        if ($this->userCanWrite()) {
            if (!$utils->getPackageFactory()->isPackageNameExist($name, $this->getProject()->getGroupId())) {
                $this->getPackage()->setName(htmlspecialchars($name));
                $utils->getPackageFactory()->update($this->getPackage());
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'package_name_exist'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'package_denied_rename'));
        }
    }

    /**
     * Moves the package from a project to another (not yet ready)
     * Move of packages is now disabled
     *
     * @param WebDAVProject $destination
     *
     * @return void
     */
    /*function move($destination) {

        if ($this->userIsAdmin() && $destination->userIsAdmin()) {
            $utils = $this->getUtils();
            if (!$utils->getPackageFactory()->isPackageNameExist($name, $destination->getGroupId())) {
                $this->getPackage()->setGroupID($destination->getGroupId());
                $utils->getPackageFactory()->update($this->getPackage());
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'package_name_exist'));
            }
        } else {
            // TODO: internationalisation
            throw new Sabre_DAV_Exception_Forbidden('no move package');
        }

    }*/

    /**
     * Creates a new release under the package
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
            if (!$utils->getReleaseFactory()->isReleaseNameExist($name, $this->getPackageId())) {
                $releaseData['name'] = htmlspecialchars($name);
                $releaseData['package_id'] = $this->getPackageId();
                $releaseData['notes'] = '';
                $releaseData['changes'] = '';
                $releaseData['status_id'] = 1;

                $relFactory = $utils->getReleaseFactory();
                $releaseId  = $relFactory->create($releaseData);
                if ($releaseId) {
                    // Set permissions
                    $releaseData['release_id'] = $releaseId;
                    $release = new FRSRelease($releaseData);
                    $relFactory->setDefaultPermissions($release);
                }
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'release_name_exist'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'release_denied_create'));
        }
    }
}
