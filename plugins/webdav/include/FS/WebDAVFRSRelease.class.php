<?php
/**
 * This class is used to mount the files of a given
 * release into the WebDAV virtual file system.
 *
 * It's an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */

require_once ('WebDAVFRSFile.class.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');

class WebDAVFRSRelease extends Sabre_DAV_Directory {

    private $user;
    private $project;
    private $package;
    private $release;

    /**
     * Constuctor of the class
     *
     * @param User $user
     * @param Project $project
     * @param FRSPackage $package
     * @param FRSRelease $release
     *
     * @return void
     */
    function __construct($user, $project, $package, $release) {

        $this->user = $user;
        $this->project = $project;
        $this->package = $package;
        $this->release = $release;

    }

    /**
     * Generates the list of files under the release
     *
     * @return Array
     */
    function getChildren() {

        $children = array();

        // Generate file list of the given release
        $files=$this->getFileList($this->getRelease());
        // Loop through the files, and create objects for each node
        foreach ($files as $file) {
            $children[] = $this->getWebDAVFRSFile($file);
        }
        return $children;

    }

    /**
     * Returns the given file
     *
     * @param String $fileName
     *
     * @return WebDAVFRSFile
     */
    function getChild($fileName) {

        $utils = $this->getUtils();
        $fileId = $utils->extractId($fileName);
        return $this->getWebDAVFRSFile($this->getFRSFileFromId($fileId));

    }

    /**
     * Returns the name of the release
     *
     * @return String
     *
     * @see lib/Sabre/DAV/Sabre_DAV_INode#getName()
     */
    function getName() {

        /* The release name is preceded by its id to keep
         *  the client able to request the release from its id
         *  also to keep the Url correct the slashes "/" in the name
         *  of the release were replaced by "_"
         */
        $name = $this->getRelease()->getReleaseID()."-".$this->getRelease()->getName();
        $name = str_replace('/', '_', $name);

        if ($this->getRelease()->isHidden()) {
            $name .= $GLOBALS['Language']->getText('plugin_webdav_common', 'hidden');
        }
        return $name;

    }

    /**
     * Returns the release date
     *
     * @return date
     */
    function getLastModified() {

        return $this->getRelease()->getReleaseDate();

    }

    /**
     * Returns the release as an object instance of FRSRelease
     *
     * @return FRSRelease
     */
    function getRelease() {

        return $this->release;

    }

    /**
     * Returns the release Id
     *
     * @return Integer
     */
    function getReleaseId() {

        return $this->getRelease()->getReleaseID();

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
     * Returns the project as an object instance of Project
     *
     * @return Project
     */
    function getProject() {

        return $this->project;

    }

    /**
     * Returns the user as an object instance of User
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
     * Returns new FRSFile from its Id
     *
     * @param Integer $fileId
     *
     * @return FRSFile
     */
    function getFRSFileFromId($fileId) {

        $frsff = new FRSFileFactory();
        return $frsff->getFRSFileFromDb($fileId, $this->getProject()->getGroupId());

    }

    /**
     * Returns a new WebDAVFRSFile from the given FRSFile
     *
     * @param FRSFile $file
     *
     * @return WebDAVFRSFile
     */
    function getWebDAVFRSFile($file) {

        return new WebDAVFRSFile($this->getUser(), $this->getProject(), $this->getPackage(), $this->getRelease(), $file);

    }

    /**
     * Generates file list of the given release
     *
     * @param FRSRelease $release
     *
     * @return Array
     */
    function getFileList($release) {

        $frsff = new FRSFileFactory();
        return $frsff->getFRSFilesFromDb($release->getReleaseID());

    }

    /**
     * returns whether the release exists or not
     *
     * @return Boolean
     */
    function exist() {

        if ($this->getRelease() && !$this->getRelease()->isDeleted()) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Check whether the user can read the release or not
     *
     * @param User $user
     *
     * @return Boolean
     */
    function userCanRead($user) {

        return (($this->getRelease()->isActive() && $this->getRelease()->userCanRead($user->getId()))
        || ($this->getRelease()->isHidden() && $this->userIsAdmin()));

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