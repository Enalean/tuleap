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
     * Log the download
     *
     * @param Docman_Version $version
     *
     * @return void
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
     * @return void
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
        parent::download($version->getFiletype(), $version->getFilesize(), $version->getPath());
    }

}

?>