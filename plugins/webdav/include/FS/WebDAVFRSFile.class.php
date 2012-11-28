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

require_once ('common/include/MIME.class.php');
require_once ('www/project/admin/permissions.php');
require_once (dirname(__FILE__).'/../WebDAVUtils.class.php');

/**
 * This class is used to maniplulate files through WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_File methods
 *
 */
class WebDAVFRSFile extends Sabre_DAV_File {

    private $user;
    private $project;
    private $package;
    private $release;
    private $file;

    /**
     * Constuctor of the class
     *
     * @param PFUser $user
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
        return $basename;

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
     * Returns a unique identifier of the file
     *
     * @return String
     */
    function getETag() {
        return '"'.$this->getUtils()->getIncomingFileMd5Sum($this->getFileLocation()).'"';
    }

    /**
     * Returns mime-type of the file
     *
     * @return String
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_File#getContentType()
     */
    function getContentType() {
        if (file_exists($this->getFileLocation())) {
            $mime = MIME::instance();
            return $mime->type($this->getFileLocation());
        }
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
     * Returns the file Id
     *
     * @return Integer
     */
    function getFileId() {

        return $this->getFile()->getFileID();

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
     * Returns the Id of the package that file belongs to
     *
     * @return Integer
     */
    function getPackageId() {

        return $this->getFile()->getPackageID();

    }

    /**
     * Returns the project
     *
     * @return Project
     */
    function getProject() {

        return $this->project;

    }

    /**
     * Returns the user
     *
     * @return PFUser
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
     * Returns if the user is superuser or File release admin
     *
     * @return Boolean
     */
    function userCanWrite() {
        $utils = $this->getUtils();
        return $utils->userCanWrite($this->getUser(), $this->getProject()->getGroupId());
    }

    /**
     * Deletes the file
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#delete()
     */
    function delete() {

        if ($this->userCanWrite()) {
            $utils = $this->getUtils();
            $result = $utils->getFileFactory()->delete_file($this->getProject()->getGroupId(), $this->getFileId());
            if ($result == 0) {
                throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }

    }

    /**
     * A wrapper to copy
     *
     * @param String $source
     * @param String $destination
     *
     * @return Boolean
     */
    function copyFile($source, $destination) {

        return copy($source, $destination);

    }

}

?>