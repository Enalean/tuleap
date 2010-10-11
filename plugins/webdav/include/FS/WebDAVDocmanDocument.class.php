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

    protected $user;
    protected $project;
    protected $document;

    /**
     * Constuctor of the class
     *
     * @param User $user
     * @param Project $project
     * @param Docman_Document $document
     *
     * @return void
     */
    function __construct($user, $project, $document) {
        $this->user = $user;
        $this->project = $project;
        $docmanItemFactory = new Docman_ItemFactory();
        $this->document = $docmanItemFactory->getItemFromDb($document->getId());
    }

    /**
     * This method is used to download the file
     *
     * @return null
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_File::get()
     */
    function get() {
        header('Content-Type: text/plain');
        header('Content-Length: 0');
        header('Content-Disposition: filename="'.$this->getItem()->getTitle().'"');
        readfile('');
        exit;
    }

    /**
     * Returns the name of the file
     *
     * @return String
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_INode::getName()
     */
    function getName() {
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getItem()->getTitle());
    }

    /**
     * Returns mime-type of the file
     *
     * @return String
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_File::getContentType()
     */
    function getContentType() {
        switch (get_class($this->getItem())) {
            case 'Docman_Wiki':
                return 'Wiki';
                break;
            case 'Docman_Link':
                return $GLOBALS['Language']->getText('plugin_webdav_common', 'link');
                break;
            case 'Docman_Empty':
                return $GLOBALS['Language']->getText('plugin_webdav_common', 'empty');
                break;
        }
    }

    /**
     * Returns the file size
     *
     * @return Integer
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_File::getSize()
     */
    function getSize() {
        return 0;
    }

    /**
     * Returns the last modification date
     *
     * @return date
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_Node::getLastModified()
     */
    function getLastModified() {
        return $this->getItem()->getUpdateDate();
    }

    /**
     * Returns the the project that document belongs to
     *
     * @return FRSProject
     */
    function getProject() {
        return $this->project;
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
     * Returns the represented document
     *
     * @return Docman_Document
     */
    function getItem() {
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