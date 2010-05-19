<?php
/**
 * This class is used to maniplulate files through WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_File methods
 *
 */

require_once (dirname(__FILE__).'/../../../../src/common/include/MIME.class.php');
require_once (dirname(__FILE__).'/../../../../src/www/project/admin/permissions.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');

class WebDAVFRSFile extends Sabre_DAV_File {

    private $user;
    private $project;
    private $package;
    private $release;
    private $file;

    /**
     * Constuctor of the class
     *
     * @param User $user
     * @param Project $project
     * @param FRSPackage $package
     * @param FRSRelease $release
     * @param FRSFile $file
     *
     * @return void
     */
    function __construct($user, $project, $package, $release, $file) {

        $this->user = $user;
        $this->project = $project;
        $this->package = $package;
        $this->release = $release;
        $this->file = $file;

    }

    /**
     * This method is used to download the file
     *
     * @return File
     */
    function get() {

        // Check for errors

        // Check if the file is not null and is not deleted
        if (!$this->getFile() || $this->getFile()->isDeleted()) {
            // File not found error
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
        }

        // Check that the file has the active status
        if (!$this->isActive()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'access_not_authorized'));
        }

        // Check if the user can download the file
        if (!$this->userCanDownload($this->getUser())) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'access_not_authorized'));
        }

        // Check if the file exists in the database
        if (! $this->fileExists()) {
            // File doesn't exist error
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
        }

        // Ceck that the file belongs to the package and the release
        if (($this->getPackageId() != $this->getGivenPackageId()) || ($this->getReleaseId() != $this->getGivenReleaseId())) {
            // File don't belong to package or release error
            throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'dont_belong'));
        }

        // Check the 2 GB limit (2^31 -1)
        $fileSize = $this->getSize();
        if ($fileSize > 2147483647) {
            // File size error
            throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
        }

        // Log the download in the Log system
        $this->logDownload($this->getUser());

        // Start download
        $fileLocation = $this->getFileLocation();
        $fp= fopen($fileLocation, 'r');
        return $fp;

    }

    /**
     * Returns the name of the file
     *
     * @return String
     */
    function getName() {

        /* The file name is preceded by its id to keep
         *  the client able to request the file from its id
         */
        $basename = basename($this->getFile()->getFileName());
        return $this->getFile()->getFileID()."-".$basename;

    }

    /**
     * Returns the last modification date
     *
     * @return date
     */
    function getLastModified() {

        return $this->getFile()->getPostDate();

    }

    /**
     * Returns the file size
     *
     * @return Integer
     */
    function getSize() {

        return $this->getFile()->getFileSize();

    }

    /**
     * Returns the file Id
     *
     * @return Integer
     */
    function getETag() {

        return $this->getFile()->getFileID();

    }

    /**
     * Returns mime-type of the file
     *
     * @return String
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_File#getContentType()
     */
    function getContentType() {

        $mime = MIME::instance();
        return $mime->type($this->getFileLocation());

    }

    /**
     * Returns the file location
     *
     * @return String
     */
    function getFileLocation() {

        return $this->getFile()->getFileLocation();

    }

    /**
     * Returns the file as an object instance of FRSFile
     *
     * @return FRSFile
     */
    function getFile() {

        return $this->file;

    }

    /**
     * Returns the Id of the release that file belongs to
     *
     * @return Integer
     */
    function getReleaseId() {

        return $this->getFile()->getReleaseID();

    }

    /**
     * Returns the given release Id
     *
     * @return Integer
     */
    function getGivenReleaseId() {

        return $this->release->getReleaseID();

    }

    /**
     * Returns the Id of the package that file belongs to
     *
     * @return Integer
     */
    function getPackageId() {

        return $this->getFile()->getPackageID();

    }

    /**
     * Returns the given package Id
     *
     * @return Integer
     */
    function getGivenPackageId() {

        return $this->package->getPackageID();

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
     * Tests whether the file is active or not
     *
     * @return Boolean
     */
    function isActive() {

        return $this->getFile()->isActive();

    }

    /**
     * Checks whether the user can download the file or not
     *
     * @param Integer $user
     *
     * @return Boolean
     */
    function userCanDownload($user) {

        return $this->getFile()->userCanDownload($user->getId());

    }

    /**
     * Tests whether the file exists in the file system or not
     *
     * @return Boolean
     */

    function fileExists() {

        return $this->getFile()->fileExists();

    }

    /**
     * Logs the download in the Log system
     *
     * @param Integer $userId
     *
     * @return Boolean
     */
    function logDownload($user) {

        return $this->getFile()->LogDownload($user->getId());

    }

    /**
     * Returns if the user is File release admin
     *
     * @return Boolean
     */
    function userIsAdmin() {

        $utils = WebDAVUtils::getInstance();
        return $utils->userIsAdmin($this->getUser(), $this->getProject()->getGroupId());

    }

}

?>