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

/**
 * This class Represents Docman documents in WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_File methods
 *
 */
class WebDAVDocmanDocument extends Sabre_DAV_File {

    protected $document;
    protected $user;

    /**
     * Constuctor of the class
     *
     * @param Docman_Document $document
     * @param User $user
     *
     * @return void
     */
    function __construct($user, $project, $maxFileSize, $document) {
        $this->document = $document;
        $this->user = $user;
    }

    /**
     * This method is used to download the file
     *
     * @return File
     */
    function get() {
        header('Content-Type: text/plain');
        header('Content-Length: 0');
        header('Content-Disposition: filename="'.$this->getDocument()->getTitle().'"');
        readfile('');
        exit;
    }

    /**
     * Returns the name of the file
     *
     * @return String
     */
    function getName() {
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getDocument()->getTitle());
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
        $item = $if->getItemFromDb($this->getDocument()->getId());
        switch (get_class($this->getDocument())) {
            case 'Docman_Wiki':
                return 'Wiki';
                break;
            case 'Docman_Link':
                return 'Link';
                break;
            case 'Docman_Empty':
                return 'Empty';
                break;
        }
    }

    /**
     * Returns the file size
     *
     * @return Integer
     */
    function getSize() {
        return 0;
    }

    /**
     * Returns the last modification date
     *
     * @return date
     */
    function getLastModified() {
        return $this->getDocument()->getUpdateDate();
    }

    /**
     * Returns the represented document
     *
     * @return Docman_Document
     */
    function getDocument() {
        return $this->document;
    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils() {

        return WebDAVUtils::getInstance();

    }

}

?>