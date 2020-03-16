<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
 * This class is used to mount the files of a given
 * release into the WebDAV virtual file system.
 *
 * It's an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */
class WebDAVFRSRelease extends Sabre_DAV_Directory
{

    private $user;
    private $project;
    private $package;
    private $release;
    private $maxFileSize;

    /**
     * Constuctor of the class
     *
     * @param PFUser $user
     * @param Project $project
     * @param FRSPackage $package
     * @param FRSRelease $release
     * @param int $maxFileSize
     *
     * @return void
     */
    public function __construct($user, $project, $package, $release, $maxFileSize)
    {
        $this->user = $user;
        $this->project = $project;
        $this->package = $package;
        $this->release = $release;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Generates the list of files under the release
     *
     * @return Array
     */
    public function getChildren()
    {
        $children = array();

        // Generate file list of the given release
        $files = $this->getFileList($this->getRelease());
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
    public function getChild($fileName)
    {
        $fileId = $this->getFileIdFromName($fileName);
        $file = $this->getWebDAVFRSFile($this->getFRSFileFromId($fileId));

        // Check for errors

        // Check if the file is not null and is not deleted
        if (!$file->getFile() || $file->getFile()->isDeleted()) {
            // File not found error
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
        }

        // Check that the file has the active status
        if (!$file->isActive()) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'access_not_authorized'));
        }

        // Check if the user can download the file
        if (!$file->userCanDownload($this->getUser())) {
            // Access denied error
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'access_not_authorized'));
        }

        // Check if the file exists in the filesystem
        if (! $file->fileExists()) {
            // File doesn't exist error
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
        }

        // Ceck that the file belongs to the package and the release
        if (($file->getPackageId() != $this->getPackage()->getPackageID()) || ($file->getReleaseId() != $this->getReleaseId())) {
            // File don't belong to package or release error
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'dont_belong'));
        }

        // Check the maximum file size limit
        $fileSize = $file->getSize();
        if ($fileSize > $this->getMaxFileSize()) {
            // File size error
            throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
        }
        return $file;
    }

    /**
     * Returns the name of the release
     *
     * @return String
     *
     * @see lib/Sabre/DAV/Sabre_DAV_INode#getName()
     */
    public function getName()
    {
        /* To keep the Url correct the slashes "/" in the name
         *  of the release were replaced by its ascii code "%2F"
         *  same for the "%" replaced by "%25"  */
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getRelease()->getName());
    }

    /**
     * Returns the release date
     *
     * @return date
     */
    public function getLastModified()
    {
        return $this->getRelease()->getReleaseDate();
    }

    /**
     * Returns the release as an object instance of FRSRelease
     *
     * @return FRSRelease
     */
    public function getRelease()
    {
        return $this->release;
    }

    /**
     * Returns the release Id
     *
     * @return int
     */
    public function getReleaseId()
    {
        return $this->getRelease()->getReleaseID();
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
     * Returns the project as an object instance of Project
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Returns the user as an object instance of User
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
     * Returns new FRSFile from its Id
     *
     * @param int $fileId
     *
     * @return FRSFile
     */
    public function getFRSFileFromId($fileId)
    {
        $frsff = new FRSFileFactory();
        return $frsff->getFRSFileFromDb($fileId, $this->getProject()->getGroupId());
    }

    /**
     * Returns the file Id from its name
     *
     * @param String $fileName
     *
     * @return int
     */
    public function getFileIdFromName($fileName)
    {
        $dao = new FRSFileDao(CodendiDataAccess::instance());
        if ($row = $dao->searchFileByNameFromRelease($fileName, $this->getReleaseId())->getRow()) {
            return $row['file_id'];
        }
        return 0;
    }

    /**
     * Returns a new WebDAVFRSFile from the given FRSFile
     *
     * @param FRSFile $file
     *
     * @return WebDAVFRSFile
     */
    public function getWebDAVFRSFile($file)
    {
        return new WebDAVFRSFile($this->getUser(), $this->getProject(), $file);
    }

    /**
     * Generates file list of the given release
     *
     * @param FRSRelease $release
     *
     * @return Array
     */
    public function getFileList($release)
    {
        $frsff = new FRSFileFactory();
        return $frsff->getFRSFilesFromDb($release->getReleaseID());
    }

    /**
     * returns whether the release exists or not
     *
     * @return bool
     */
    public function exist()
    {
        return($this->getRelease() && !$this->getRelease()->isDeleted());
    }

    /**
     * Checks whether the user can read the release or not
     *
     *
     */
    public function userCanRead(PFUser $user): bool
    {
        return (($this->getRelease()->isActive() && $this->getRelease()->userCanRead($user->getId()))
        || ($this->getRelease()->isHidden() && $this->userIsAdmin($user)));
    }

    /**
     * Returns if the user is superuser, project admin or File release admin
     *
     */
    public function userIsAdmin(PFUser $user): bool
    {
        $utils = $this->getUtils();
        return $utils->userIsAdmin($user, $this->getProject()->getID());
    }

    /**
     * Returns if the user is superuser or File release admin
     *
     * @return bool
     */
    public function userCanWrite()
    {
        $utils = $this->getUtils();
        return $utils->userCanWrite($this->getUser(), $this->getProject()->getGroupId());
    }

    /**
     * Deletes the release
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#delete()
     */
    public function delete()
    {
        if ($this->userCanWrite()) {
            $utils = $this->getUtils();
            $result = $utils->getReleaseFactory()->delete_release($this->getProject()->getGroupId(), $this->getReleaseId());
            if ($result == 0) {
                throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'release_not_available'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'release_denied_delete'));
        }
    }

    /**
     * Renames the release
     *
     * @param String $name
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#setName($name)
     */
    public function setName($name)
    {
        $utils = $this->getUtils();
        if ($this->userCanWrite()) {
            if (!$utils->getReleaseFactory()->isReleaseNameExist($name, $this->getPackage()->getPackageID())) {
                $this->getRelease()->setName(htmlspecialchars($name));
                $utils->getReleaseFactory()->update($this->getRelease()->toArray());
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'release_name_exist'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'release_denied_rename'));
        }
    }

    /**
     * Moves the release under another package
     *
     * @param WebDAVFRSPackage $destination
     *
     * @return void
     */
    public function move($destination)
    {
        $utils = $this->getUtils();
        if ($utils->getReleaseFactory()->userCanUpdate($this->getProject()->getGroupId(), $this->getReleaseId()) && $destination->userCanWrite()) {
            if (!$utils->getReleaseFactory()->isReleaseNameExist($this->getName(), $destination->getPackageId())) {
                // We don't allow moving an active release under a hidden package.
                if (!$destination->getPackage()->isHidden() || $this->getRelease()->isHidden()) {
                    $this->getRelease()->setPackageID($destination->getPackageId());
                    $utils->getReleaseFactory()->update($this->getRelease()->toArray());
                } else {
                    throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'release_hidden'));
                }
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'release_name_exist'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'release_denied_move'));
        }
    }

    /**
     * Creates a new file under the release
     *
     * @param String $name
     * @param Binary $data
     *
     * @return void
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_Directory#createFile($name, $data)
     */
    public function createFile($name, $data = null)
    {
        if ($this->userCanWrite()) {
            $utils = $this->getUtils();

            // Create file in the staging area
            $this->createFileIntoIncoming($name, $data);
            $fileSize = $utils->getIncomingFileSize($name);
            if ($fileSize > $this->getMaxFileSize()) {
                throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }

            $release = $this->getRelease();

            $newFile = new FRSFile();
            $newFile->setRelease($release);
            $newFile->setFileName($name);
            $newFile->setProcessorID(100);
            $newFile->setTypeID(100);
            $newFile->setUserId($this->getUser()->getId());
            try {
                $frsff = $utils->getFileFactory();
                $frsff->createFile($newFile);
                $utils->getReleaseFactory()->emailNotification($release);
            } catch (Exception $e) {
                    throw new Sabre_DAV_Exception_BadRequest($e->getMessage());
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_create'));
        }
    }

    /**
     * A wrapper to unlink
     *
     * @param String $path
     *
     * @return bool
     */
    public function unlinkFile($path)
    {
        return unlink($path);
    }

    /**
     * A wrapper to fopen
     *
     * @param String $path
     *
     * @return bool
     */
    public function openFile($path)
    {
        return fopen($path, 'x');
    }

    /**
     * A wrapper to stream_copy_to_stream
     *
     * @param Binary $data
     * @param File $file
     *
     * @return bool
     */
    public function streamCopyToStream($data, $file)
    {
        return stream_copy_to_stream($data, $file);
    }

    /**
     * A wrapper to fclose
     *
     * @param File $file
     *
     * @return bool
     */
    public function closeFile($file)
    {
        return fclose($file);
    }

    /**
     * Creates the file into incoming directory
     *
     * @param String $name
     * @param Binary $data
     *
     * @return void
     */
    public function createFileIntoIncoming($name, $data)
    {
        $path = $GLOBALS['ftp_incoming_dir'] . '/' . $name;
        if (file_exists($path)) {
            if (!$this->unlinkFile($path)) {
                throw new Sabre_DAV_Exception($GLOBALS['Language']->getText('plugin_webdav_upload', 'delete_file_fail'));
            }
        }
        if (!$file = $this->openFile($path)) {
            throw new Sabre_DAV_Exception($GLOBALS['Language']->getText('plugin_webdav_upload', 'create_file_fail'));
        }

        if ($this->streamCopyToStream($data, $file) === false) {
            throw new Sabre_DAV_Exception($GLOBALS['Language']->getText('plugin_webdav_upload', 'write_file_fail'));
        }

        if (!$this->closeFile($file)) {
            throw new Sabre_DAV_Exception($GLOBALS['Language']->getText('plugin_webdav_upload', 'close_file_fail'));
        }
    }
}
