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
 * This class Represents Docman documents in WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_File methods
 *
 */
class WebDAVDocmanFile extends WebDAVDocmanDocument {

    /**
     * This method is used to download the file
     *
     * @return File
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::get()
     */
    function get() {
        $dif = $this->getItemFactory();
        $item = $dif->getItemFromDb($this->getItem()->getId());
        $version = $item->getCurrentVersion();
        if (file_exists($version->getPath())) {
            if ($this->getSize() <= $this->getMaxFileSize()) {
                $this->logDownload($version);
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
                $dif = new Docman_ItemFactory();
                $item = $dif->getItemFromDb($this->getItem()->getId());
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
        $if = new Docman_ItemFactory();
        $item = $if->getItemFromDb($this->getItem()->getId());
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
        $if = new Docman_ItemFactory();
        $item = $if->getItemFromDb($this->getItem()->getId());
        $version = $item->getCurrentVersion();
        return $version->getFilesize();
    }

    /**
     * Returns the max file size
     *
     * @return Integer
     */
    function getMaxFileSize() {
        return $this->maxFileSize;
    }

    /**
     * Log the download
     *
     * @param Docman_Version $version
     *
     * @return null
     */
    function logDownload($version) {
        $logger = new Docman_Log();
        $params = array('group_id' => $this->getProject()->getGroupId(),
                        'item'     => $this->getItem(),
                        'version'  => $version->getNumber(),
                        'user'     => $this->getUser(),
                        'event'    => 'plugin_docman_event_access');
        $logger->log($params['event'], $params);
    }

    /**
     * Downloads the file
     *
     * @param Docman_Version $version
     *
     * @return null
     */
    function download($version) {
        // Wait for watermarking
        $em =& EventManager::instance();
        $em->processEvent('plugin_docman_file_before_download', array(
                                             'item'            => $this->getItem(),
                                             'user'            => $this->getUser(),
                                             'version'         => $version,
                                             'docmanControler' => null
        ));
        // Download the file
        header('Content-Type: '. $version->getFiletype());
        header('Content-Length: '. $version->getFilesize());
        header('Content-Disposition: filename="'. $version->getFilename() .'"');
        readfile($version->getPath());
        exit;
    }

    /**
     * Returns a new instance of Docman_ItemFactory
     *
     * @return Docman_ItemFactory
     */
    function getItemFactory() {
        return new Docman_ItemFactory();
    }

}

?>