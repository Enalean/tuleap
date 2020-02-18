<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use Tuleap\WebDAV\Docman\DocumentDownloader;

/**
 * This class Represents Docman documents in WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_File methods
 *
 */
class WebDAVDocmanDocument extends Sabre_DAV_File
{

    protected $user;
    protected $project;
    protected $item;
    /**
     * @var DocumentDownloader
     */
    private $document_downloader;

    /**
     * Constuctor of the class
     *
     * @param PFUser $user
     * @param Docman_Document $item
     *
     * @return void
     */
    public function __construct($user, Project $project, $item, DocumentDownloader $document_downloader)
    {
        $this->user = $user;
        $this->project = $project;
        $docmanItemFactory = Docman_ItemFactory::instance($project->getID());
        $this->item = $docmanItemFactory->getItemFromDb($item->getId());
        $this->document_downloader = $document_downloader;
    }

    /**
     * This method is used to download the file
     *
     * @return void
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_File::get()
     */
    public function get()
    {
        // in this case download just an empty file
        $this->download('application/octet-stream', 0, '');
    }

    /**
     * Returns the name of the file
     *
     * @return string
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_INode::getName()
     */
    public function getName()
    {
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getItem()->getTitle());
    }

    /**
     * Returns mime-type of the file
     *
     * @return string|null
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_File::getContentType()
     * @psalm-suppress ImplementedReturnTypeMismatch Return type of the library is incorrect
     */
    public function getContentType()
    {
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
        return null;
    }

    /**
     * Returns the file size
     *
     * @return int
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_File::getSize()
     */
    public function getSize()
    {
        return 0;
    }

    /**
     * Returns the last modification date
     *
     * @return int
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_Node::getLastModified()
     */
    public function getLastModified()
    {
        return $this->getItem()->getUpdateDate();
    }

    /**
     * Returns the the project that document belongs to
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Returns the user
     *
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns the represented document
     *
     * @return Docman_Document
     */
    public function getItem()
    {
        return $this->item;
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
     * Downloads the document
     *
     * @param String  $fileType
     * @param int $fileSize
     * @param String  $path
     *
     * @return void
     */
    public function download($fileType, $fileSize, $path)
    {
        $this->document_downloader->downloadDocument($this->getName(), $fileType, $fileSize, $path);
    }

    /**
     * Delete the document
     *
     * @return void
     */
    public function delete()
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params['action']   = 'delete';
            $params['group_id'] = $this->getProject()->getID();
            $params['confirm']  = true;
            $params['id']       = $this->getItem()->getId();
            $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }

    /**
     * Rename the document
     *
     * Even if rename is forbidden some silly WebDAV clients (ie : Micro$oft's one)
     * will bypass that and try to delete the original document
     * then upload another one with the same content and a new name
     * Which is very different from just renaming the document
     *
     * @param String $name New name of the document
     *
     * @return void
     */
    public function setName($name)
    {
        if ($this->getUtils()->isWriteEnabled()) {
            try {
                // Request
                $params['action']   = 'update';
                $params['group_id'] = $this->getProject()->getID();
                $params['confirm']  = true;

                // Item details
                $params['item']['id']    = $this->getItem()->getId();
                $params['item']['title'] = $name;

                $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
            } catch (Exception $e) {
                throw new Sabre_DAV_Exception_MethodNotAllowed($e->getMessage());
            }
        } else {
            throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_rename'));
        }
    }
}
