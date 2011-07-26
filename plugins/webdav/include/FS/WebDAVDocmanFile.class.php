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

require_once (dirname(__FILE__).'/../../../docman/include/Docman_Log.class.php');
require_once('WebDAVDocmanDocument.class.php');

/**
 * This class Represents Docman files & embedded files in WebDAV
 */
class WebDAVDocmanFile extends WebDAVDocmanDocument {

    private static $maxFileSize;

    /**
     * This method is used to download the file
     *
     * @return void
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::get()
     */
    function get() {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();

        if (file_exists($version->getPath())) {
            if ($this->getSize() <= $this->getMaxFileSize()) {
                $this->download($version);
            } else {
                throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }
        } else {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
        }
    }

    /**
     * Returns the name of the file
     *
     * @return String
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::getName()
     */
    function getName() {
        switch (get_class($this->getItem())) {
            case 'Docman_File':
                $item = $this->getItem();
                $version = $item->getCurrentVersion();
                return $version->getFilename();
                break;
            case 'Docman_EmbeddedFile':
                return $this->getItem()->getTitle();
        }
    }

    /**
     * Returns mime-type of the file
     *
     * @return String
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::getContentType()
     */
    function getContentType() {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();
        return $version->getFiletype();
    }

    /**
     * Returns the file size
     *
     * @return Integer
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::getSize()
     */
    function getSize() {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();
        return $version->getFilesize();
    }

    /**
     * Returns a unique identifier of the file
     *
     * @return String
     */
    function getETag() {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();
        return '"'.$this->getUtils()->getIncomingFileMd5Sum($version->getPath()).'"';
    }

    /**
     * Returns the max file size
     *
     * @return Integer
     */
    function getMaxFileSize() {
        return self::$maxFileSize;
    }

    /**
     * Sets the max file size
     *
     * @param Integer $maxFileSize
     *
     * @return void
     */
    function setMaxFileSize($maxFileSize) {
        self::$maxFileSize = $maxFileSize;
    }

    /**
     * Downloads the file
     *
     * @param Docman_Version $version
     *
     * @return void
     */
    function download($version) {
        $version->preDownload($this->getItem(), $this->getUser());
        // Download the file
        parent::download($version->getFiletype(), $version->getFilesize(), $version->getPath());
    }

    /**
     * Create a new version of the file
     *
     * @param $data
     *
     * @return void
     */
    function put($data) {
        $docmanPermissionManager = $this->getUtils()->getDocmanPermissionsManager($this->getProject());
        if ($this->getUtils()->isWriteEnabled() && $docmanPermissionManager->userCanWrite($this->getUser(), $this->getItem()->getId())) {
            $versionFactory = $this->getUtils()->getVersionFactory();
            $nextNb         = $versionFactory->getNextVersionNumber($this->getItem());
            if($nextNb === false) {
                $number     = 1;
                $_changelog = 'Initial version';
            } else {
                $number     = $nextNb;
                $_changelog = '';
            }
            $fs   = $this->getUtils()->getFileStorage();
            $path = $fs->store(stream_get_contents($data), $this->getProject()->getGroupId(), $this->getItem()->getId(), $number);
            if ($path) {
                $_filesize = PHP_BigFile::getSize($path);
                if ($_filesize <= $this->getMaxFileSize()) {
                    $_filename = $this->getName();
                    $_filetype = mime_content_type($path);
                    $vArray    = array('item_id'   => $this->getItem()->getId(),
                                       'number'    => $number,
                                       'user_id'   => $this->getUser()->getId(),
                                       'label'     => '',
                                       'changelog' => $_changelog,
                                       'filename'  => $_filename,
                                       'filesize'  => $_filesize,
                                       'filetype'  => $_filetype, 
                                       'path'      => $path,
                                       'date'      => '');
                    if (!$versionFactory->create($vArray)) {
                        throw new WebDAVExceptionServerError($GLOBALS['Language']->getText('plugin_webdav_upload', 'create_file_fail'));
                    }
                } else {
                    throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
                }
            } else {
                throw new WebDAVExceptionServerError($GLOBALS['Language']->getText('plugin_webdav_upload', 'write_file_fail'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_new_version'));
        }
    }

    /**
     * Deletes the file
     *
     * @return void
     */
    function delete() {
        $docmanPermissionManager = $this->getUtils()->getDocmanPermissionsManager($this->getProject());
        if ($this->getUtils()->isWriteEnabled() && $docmanPermissionManager->userCanWrite($this->getUser(), $this->getItem()->getId())) {
            // Mark the file as deleted
            $item = $this->getItem();
            $itemFactory = $this->getUtils()->getDocmanItemFactory();
            $itemFactory->delete($item);
            // Delete all its versions
            $versionFactory = $this->getUtils()->getVersionFactory();
            if ($versions = $versionFactory->getAllVersionForItem($this->getItem())) {
                if (count($versions)) {
                    foreach ($versions as $version) {
                        $versionFactory->deleteSpecificVersion($this->getItem(), $version->getNumber());
                    }
                }
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }

    /**
     * Rename an embedded file
     * We don't allow renaming files
     *
     * Even if rename is forbidden some silly WebDAV clients (ie : Micro$oft's one)
     * will bypass that and try to delete the original file
     * then upload another one with the same content and a new name
     * Which is very different from just renaming the file
     *
     * @param String $name New name of the document
     *
     * @return void
     */
    function setName($name) {
        switch (get_class($this->getItem())) {
            case 'Docman_File':
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_rename'));
                break;
            case 'Docman_EmbeddedFile':
                parent::setName($name);
                break;
        }
    }

}

?>