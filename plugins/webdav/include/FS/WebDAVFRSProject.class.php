<?php
/**
 * This class lists the packages of a given project
 *
 * It is an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */

require_once ('WebDAVFRSPackage.class.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');

class WebDAVFRSProject extends Sabre_DAV_Directory {

    private $project;
    private $user;

    /**
     * Constuctor of the class
     *
     * @param User $user
     * @param Project $project
     *
     * @return void
     */
    function __construct($user, $project) {

        $this->user = $user;
        $this->project = $project;

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

        $utils = $this->getUtils();
        $packageId = $utils->extractId($packageName);
        $package = $this->getWebDAVPackage($this->getFRSPackageFromId($packageId));

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
     * Returns the name of the project
     *
     * @return String
     *
     * @see lib/Sabre/DAV/Sabre_DAV_INode#getName()
     */
    function getName() {

        return $this->getProject()->getGroupId()."-".$this->getProject()->getUnixName();

    }

    /**
     * Projects don't have a last modified date this
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
     * Return an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils() {

        return WebDAVUtils::getInstance();

    }

    /**
     * Returns a new FRSPackage from its Id
     *
     * @param Integer $packageId
     *
     * @return FRSPackage
     */
    function getFRSPackageFromId($packageId) {

        $frspf = new FRSPackageFactory();
        return $frspf->getFRSPackageFromDb($packageId, $this->getGroupId());

    }

    /**
     * Returns a new WebDAVFRSPackage from the given FRSPackage
     *
     * @param FRSPackage $package
     *
     * @return WebDAVFRSPackage
     */
    function getWebDAVPackage($package) {

        return new WebDAVFRSPackage($this->getUser(), $this->getProject(), $package);

    }

    /**
     * Generates package list of the given GroupId
     *
     * @param Integer $groupId
     *
     * @return Array
     */
    function getPackageList($project) {

        $frspf = new FRSPackageFactory();
        return $frspf->getFRSPackagesFromDb($project->getGroupId());

    }

    /**
     * Returns whether the project exist or not
     *
     * @return Boolean
     */
    function exist() {

        // D refers to deleted
        return !$this->getProject()->error_state || !$this->getProject()->getStatus() == 'D';

    }

    /**
     * Returns whether the project is active or not
     *
     * @return Boolean
     */
    function isActive() {

        return $this->getProject()->isActive();

    }

    /**
     * Returns whether the project uses files or not
     *
     * @return Boolean
     */
    function usesFile() {

        return $this->getProject()->usesFile();

    }

    /**
     * Check whether the user can read the project or not
     *
     * @param User $user
     *
     * @return Boolean
     */
    function userCanRead() {

        return ($this->getProject()->isPublic() || $this->getProject()->userIsMember());

    }

    /**
     * Tests if the user is Superuser, project admin or File release admin
     *
     * @return Boolean
     */
    function userIsAdmin() {

        $utils = $this->getUtils();
        return $utils->userIsAdmin($this->getUser(), $this->getGroupId());

    }

}

?>