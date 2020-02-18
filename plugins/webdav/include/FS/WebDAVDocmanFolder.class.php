<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved
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
 * This class Represents Docman folders in WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_Directory methods
 */
class WebDAVDocmanFolder extends Sabre_DAV_Directory
{

    private $user;
    private $project;
    protected $item;

    /**
     * Constructor of the class
     *
     * @param PFUser $user
     * @param Docman_Folder $item
     *
     * @return void
     */
    public function __construct($user, Project $project, $item)
    {
        $this->user = $user;
        $this->project = $project;
        $this->item = $item;
    }

    /**
     * Returns the max file size
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return (int) ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
    }

    /**
     * Returns the content of the folder
     * including indication about duplicate entries
     *
     * @return Array
     */
    public function getChildList()
    {
        $children = array();
        // hey ! for docman never add something in WebDAVUtils, docman may be not present ;)
        $docmanItemFactory = $this->getUtils()->getDocmanItemFactory();
        $nodes = $docmanItemFactory->getChildrenFromParent($this->getItem());
        $docmanPermissionManager = $this->getUtils()->getDocmanPermissionsManager($this->getProject());

        foreach ($nodes as $node) {
            if ($docmanPermissionManager->userCanAccess($this->getUser(), $node->getId())) {
                $class = get_class($node);
                switch ($class) {
                    case 'Docman_File':
                        $item = $docmanItemFactory->getItemFromDb($node->getId());
                        $version = $item->getCurrentVersion();
                        $index = $version->getFilename();
                        $method = 'getWebDAVDocmanFile';
                        break;
                    case 'Docman_EmbeddedFile':
                        $index = $node->getTitle();
                        $method = 'getWebDAVDocmanFile';
                        break;
                    case 'Docman_Empty':
                    case 'Docman_Wiki':
                    case 'Docman_Link':
                        $index = $node->getTitle();
                        $method = 'getWebDAVDocmanDocument';
                        break;
                    default:
                        $index = $node->getTitle();
                        $method = 'getWebDAVDocmanFolder';
                        break;
                }

                // When it's a duplicate say it, so it can be processed later
                foreach ($children as $key => $value) {
                    if (strcasecmp($key, $index) === 0) {
                        $children[$key]   = 'duplicate';
                        $children[$index] = 'duplicate';
                    }
                }
                if (!isset($children[$index])) {
                    $children[$index] = call_user_func(array($this,$method), $node);
                }
            }
        }
        return $children;
    }

    /**
     * Returns the visible content of the folder
     *
     * @return Sabre_DAV_INode[]
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_ICollection::getChildren()
     */
    public function getChildren()
    {
        $children = $this->getChildList();
        // Remove all duplicate elements
        foreach ($children as $key => $node) {
            if ($node === 'duplicate') {
                unset($children[$key]);
            }
        }
        return $children;
    }

    /**
     * Returns the given node
     *
     * @param String $name
     *
     * @return Sabre_DAV_INode
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_Directory::getChild()
     */
    public function getChild($name)
    {
        $name = $this->getUtils()->retrieveName($name);
        $children = $this->getChildList();

        if (!isset($children[$name])) {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'docman_item_not_available'));
        } elseif ($children[$name] === 'duplicate') {
            throw new Sabre_DAV_Exception_Conflict($GLOBALS['Language']->getText('plugin_webdav_common', 'docman_item_duplicated'));
        } else {
            return $children[$name];
        }
    }

    /**
     * Returns the name of the folder
     *
     * @return String
     *
     * @see plugins/webdav/include/lib/Sabre/DAV/Sabre_DAV_INode::getName()
     */
    public function getName()
    {
        if ($this->isDocmanRoot()) {
            // case of the root
            return 'Documents';
        }
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getItem()->getTitle());
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
     * Returns the represented folder
     *
     * @return Docman_Folder
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Returns the project
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
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public function getUtils()
    {
        return WebDAVUtils::getInstance();
    }

    /**
     * Tell if the folder is docman root
     *
     * @return bool
     */
    public function isDocmanRoot()
    {
        return !$this->getItem()->getParentId();
    }

    /**
     * Returns a new WebDAVDocmanFile
     *
     * @params Docman_File $item
     *
     * @return WebDAVDocmanFile
     */
    public function getWebDAVDocmanFile($item)
    {
        return new WebDAVDocmanFile($this->user, $this->getProject(), $item, new DocumentDownloader());
    }

    /**
     * Returns a new WebDAVDocmanEmpty
     *
     * @params mixed $item
     *
     * @return WebDAVDocmanDocument
     */
    public function getWebDAVDocmanDocument($item)
    {
        return new WebDAVDocmanDocument($this->user, $this->getProject(), $item, new DocumentDownloader());
    }

    /**
     * Returns a new WebDAVDocmanFolder
     *
     * @params Docman_Folder $folder
     *
     * @return WebDAVDocmanFolder
     */
    public function getWebDAVDocmanFolder($folder)
    {
        return new WebDAVDocmanFolder($this->user, $this->getProject(), $folder);
    }

    /**
     * Create a new docman folder
     *
     * @param String $name Name of the folder to create
     *
     * @return void
     */
    public function createDirectory($name)
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params['action']   = 'createItem';
            $params['group_id'] = $this->getProject()->getGroupId();
            $params['ordering'] = 'beginning';
            $params['confirm']  = true;

            // Item details
            $params['item']['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
            $params['item']['parent_id'] = $this->getItem()->getId();
            $params['item']['title']     = $name;

            $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'folder_denied_create'));
        }
    }

    /**
     * Creates a new document under the folder
     *
     * @param String $name Name of the document
     * @param Binary $data Content of the document
     *
     * @return void
     */
    public function createFile($name, $data = null)
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params['action']   = 'createItem';
            $params['group_id'] = $this->getProject()->getGroupId();
            $params['ordering'] = 'beginning';
            $params['confirm']  = true;

            // File stuff
            $params['file_name']      = $name;
            $params['upload_content'] = stream_get_contents($data);
            if (strlen($params['upload_content']) <= $this->getMaxFileSize()) {
                $params['item']['item_type']      = PLUGIN_DOCMAN_ITEM_TYPE_FILE;
                $params['item']['parent_id']      = $this->getItem()->getId();
                $params['item']['title']          = $name;

                $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
            } else {
                throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_create'));
        }
    }

    /**
     * Rename a docman folder
     *
     * Even if rename is forbidden some silly WebDAV clients (ie : Micro$oft's one)
     * will bypass that and try to delete the original directory
     * then upload another one with the same content and a new name
     * Which is very different from just renaming the directory
     *
     * @param String $name New name of the folder
     *
     * @return void
     */
    public function setName($name)
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params['action']   = 'update';
            $params['group_id'] = $this->getProject()->getGroupId();
            $params['confirm']  = true;

            // Item details
            $params['item']['id']    = $this->getItem()->getId();
            $params['item']['title'] = $name;

            $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
        } else {
            throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'folder_denied_rename'));
        }
    }

    /**
     * Delete the folder
     *
     * @return void
     */
    public function delete()
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params['action']   = 'delete';
            $params['group_id'] = $this->getProject()->getGroupId();
            $params['confirm']  = true;
            $params['id']       = $this->getItem()->getId();
            $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }
}
