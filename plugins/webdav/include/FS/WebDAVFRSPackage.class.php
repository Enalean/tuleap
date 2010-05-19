<?php
/**
 * This class is used to mount the releases of a given
 * package into the WebDAV virtual file system.
 *
 * It's an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */

require_once ('WebDAVFRSRelease.class.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');

class WebDAVFRSPackage extends Sabre_DAV_Directory {

    private $user;
    private $project;
    private $package;

    /**
     * Constructor of the class
     *
     * @param User $user
     * @param Project $project
     * @param FRSPackage $package
     *
     * @return void
     */
    function __construct($user, $project, $package) {

        $this->user = $user;
        $this->project = $project;
        $this->package = $package;

    }

    /**
     * Generates the list of releases under the package
     *
     * @return Array
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_IDirectory#getChildren()
     */
    function getChildren() {

        $children = array();

        // Generate release list of the given package
        $releases=$this->getReleaseList($this->getPackage());
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
    function getChild($releaseName) {

        $utils = $this->getUtils();
        $releaseId = $utils->extractId($releaseName);
        $release = $this->getWebDAVRelease($this->getFRSReleaseFromId($releaseId));

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
    function getName() {

        /* The package name is preceded by it's id to keep
         *  the client able to request the package from it's id
         *  also to keep the Url correct the slashes "/" in the name
         *  of the package were replaced by "_" */
        $name = $this->getPackage()->getPackageID()."-".$this->getPackage()->getName();
        $name = str_replace('/', '_', $name);

        if ($this->getPackage()->isHidden()) {
            $name .= $GLOBALS['Language']->getText('plugin_webdav_common', 'hidden');
        }
        return $name;

    }

    /**
     * Packages don't have a last modified date this
     * is used only suit the class Sabre_DAV_Node
     *
     * @return NULL
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    function getLastModified() {

        return;

    }

    /**
     * Returns the package as an object instance of FRSPackage
     *
     * @return FRSPackage
     */
    function getPackage() {

        return $this->package;

    }

    /**
     * Returns the package Id
     *
     * @return Integer
     */
    function getPackageId() {

        return $this->getPackage()->getPackageID();

    }

    /**
     * Returns the id of the project that package belongs to
     *
     * @return FRSProject
     */
    function getProject() {

        return $this->project;

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
     * Return an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils() {

        return WebDAVUtils::getInstance();

    }

    /**
     * Returns a new FRSRelease from its Id
     *
     * @param Integer $releaseId
     *
     * @return FRSRelease
     */
    function getFRSReleaseFromId($releaseId) {

        $frsrf = new FRSReleaseFactory();
        return $frsrf->getFRSReleaseFromDb($releaseId, $this->getProject()->getGroupId(), $this->getPackageId());

    }

    /**
     * Returns a new WebDAVFRSRelease from the given release
     *
     * @param FRSrelease $release
     *
     * @return WebDAVFRSRelease
     */
    function getWebDAVRelease($release) {

        return new WebDAVFRSRelease($this->getUser(), $this->getProject(), $this->getPackage(), $release);

    }

    /**
     * Generates release list of the given package
     *
     * @param FRSPackage $package
     *
     * @return Array
     */
    function getReleaseList($package) {

        $frsrf = new FRSReleaseFactory();
        return $frsrf->getFRSReleasesFromDb($package->getPackageId());

    }

    /**
     * Returns whether the package exists or not
     *
     * @return Boolean
     */
    function exist() {

        if ($this->getPackage() && !$this->getPackage()->isDeleted()) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Checks whether the user can read the package or not
     *
     * @param User $user
     *
     * @return Boolean
     */
    function userCanRead($user) {

        return (($this->getPackage()->isActive() && $this->getPackage()->userCanRead($user->getId()))
        || ($this->getPackage()->isHidden() && $this->userIsAdmin()));

    }

    /**
     * Returns if the user is File release admin
     *
     * @return Boolean
     */
    function userIsAdmin() {

        $utils = $this->getUtils();
        return $utils->userIsAdmin($this->getUser(), $this->getProject()->getGroupId());

    }

}

?>