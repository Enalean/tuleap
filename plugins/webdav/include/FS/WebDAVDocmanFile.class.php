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
        $group_id = $this->getProject()->getGroupId();
        $versionFactory = new Docman_VersionFactory();
        $versionFactory->callVersionEvents($this->getItem(), $this->getUser(), $version, $group_id);
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
        if ($docmanPermissionManager->userCanWrite($this->getUser(), $this->getItem()->getId())) {
            $versionFactory = $this->getUtils()->getVersionFactory();
            $nextNb         = $versionFactory->getNextVersionNumber($this->getItem());
            if($nextNb === false) {
                $number       = 1;
                $_changelog   = 'Initial version';
            } else {
                $number       = $nextNb;
                $_changelog   = '';
            }
            $fs             = $this->getUtils()->getFileStorage();
            $path           = $fs->store(stream_get_contents($data), $this->getProject()->getGroupId(), $this->getItem()->getId(), $number);
            $_filename      = $this->getName();
            $_filesize      = filesize($path);
            $_filetype      = mime_content_type($path);
            $vArray         = array('item_id'   => $this->getItem()->getId(),
                                    'number'    => $number,
                                    'user_id'   => $this->getUser()->getId(),
                                    'label'     => '',
                                    'changelog' => $_changelog,
                                    'filename'  => $_filename,
                                    'filesize'  => $_filesize,
                                    'filetype'  => $_filetype, 
                                    'path'      => $path,
                                    'date'      => '');
            $vId            = $versionFactory->create($vArray);
            $vArray['id']   = $vId;
            $vArray['date'] = time();
            $newVersion     = new Docman_Version($vArray);
            $this->getItem()->setCurrentVersion($newVersion);
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_new_version'));
        }
    }

}

?>