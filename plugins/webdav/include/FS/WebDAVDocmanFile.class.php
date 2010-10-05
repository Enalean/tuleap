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
     */
    function get() {
        $if = new Docman_ItemFactory();
        $item = $if->getItemFromDb($this->document->getId());
        $version = $item->getCurrentVersion();
        if (file_exists($version->getPath())) {
            $em =& EventManager::instance();
            $em->processEvent('plugin_docman_file_before_download', array(
                                             'item'            => $this->document,
                                             'user'            => $this->user,
                                             'version'         => $version,
                                             'docmanControler' => null
            ));
            header('Content-Type: '. $version->getFiletype());
            header('Content-Length: '. $version->getFilesize());
            header('Content-Disposition: filename="'. $version->getFilename() .'"');
            readfile($version->getPath());
            exit;
        } else {
            // TODO : internationalization
            throw new Sabre_DAV_Exception_FileNotFound('File not found on the filesystem');
        }
    }

    /**
     * Returns the name of the file
     *
     * @return String
     */
    function getName() {
        switch (get_class($this->document)) {
            case 'Docman_File':
                $dif = new Docman_ItemFactory();
                $item = $dif->getItemFromDb($this->document->getId());
                $version = $item->getCurrentVersion();
                return $version->getFilename();
                break;
            case 'Docman_EmbeddedFile':
                return $this->document->getTitle().'.html';
        }
    }

    /**
     * Returns mime-type of the file
     *
     * @return String
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_File#getContentType()
     */
    function getContentType() {
        $if = new Docman_ItemFactory();
        $item = $if->getItemFromDb($this->document->getId());
        $version = $item->getCurrentVersion();
        return $version->getFiletype();
    }

    /**
     * Returns the file size
     *
     * @return Integer
     */
    function getSize() {
        $if = new Docman_ItemFactory();
        $item = $if->getItemFromDb($this->document->getId());
        $version = $item->getCurrentVersion();
        return $version->getFilesize();
    }

}

?>