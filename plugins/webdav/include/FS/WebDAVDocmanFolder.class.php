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

class WebDAVDocmanFolder extends Sabre_DAV_Directory
{
    private const DUPLICATE                                 = 'duplicate';
    private const ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV = 'exists-not-displayed';

    private $user;
    private $project;
    protected $item;
    /**
     * @var WebDAVUtils|null
     */
    private $utils;

    /**
     * @param PFUser $user
     * @param Docman_Folder $item
     */
    public function __construct($user, Project $project, $item, ?WebDAVUtils $utils = null)
    {
        $this->user = $user;
        $this->project = $project;
        $this->item = $item;
        $this->utils = $utils;
    }

    protected function getMaxFileSize(): int
    {
        return (int) ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
    }

    /**
     * Returns the content of the folder
     * including indication about duplicate entries
     */
    public function getChildList(): array
    {
        $children = [];
        $docmanItemFactory = $this->getUtils()->getDocmanItemFactory();
        $nodes = $docmanItemFactory->getChildrenFromParent($this->getItem());
        $docmanPermissionManager = $this->getUtils()->getDocmanPermissionsManager($this->getProject());

        foreach ($nodes as $node) {
            if ($docmanPermissionManager->userCanAccess($this->getUser(), $node->getId())) {
                $class = get_class($node);
                switch ($class) {
                    case Docman_File::class:
                        $item = $docmanItemFactory->getItemFromDb($node->getId());
                        assert($item instanceof Docman_File);
                        $version = $item->getCurrentVersion();
                        $this->appendChildren($children, $version->getFilename(), new WebDAVDocmanFile($this->user, $this->getProject(), $item, new DocumentDownloader(), $this->getUtils()));
                        break;
                    case Docman_EmbeddedFile::class:
                        $item = $docmanItemFactory->getItemFromDb($node->getId());
                        assert($item instanceof Docman_EmbeddedFile);
                        $this->appendChildren($children, $node->getTitle(), new WebDAVDocmanFile($this->user, $this->getProject(), $item, new DocumentDownloader(), $this->getUtils()));
                        break;
                    case Docman_Empty::class:
                    case Docman_Wiki::class:
                    case Docman_Link::class:
                        $this->appendChildren($children, $node->getTitle(), self::ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV);
                        break;
                    default:
                        $this->appendChildren($children, $node->getTitle(), new WebDAVDocmanFolder($this->user, $this->getProject(), $node));
                        break;
                }
            }
        }
        return $children;
    }

    private function appendChildren(array &$children, string $index, $item): void
    {
        // When it's a duplicate say it, so it can be processed later
        foreach ($children as $key => $value) {
            if (strcasecmp($key, $index) === 0) {
                $children[$key] = self::DUPLICATE;
            }
        }
        if (! isset($children[$index])) {
            $children[$index] = $item;
        }
    }

    /**
     * Returns the visible content of the folder
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren(): array
    {
        $children = $this->getChildList();
        // Remove all duplicate elements
        foreach ($children as $key => $node) {
            if ($node === self::DUPLICATE || $node === self::ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV) {
                unset($children[$key]);
            }
        }
        return $children;
    }

    /**
     * Returns the given node
     *
     * @param string $name
     */
    public function getChild($name): Sabre_DAV_INode
    {
        $name = $this->getUtils()->retrieveName($name);
        $children = $this->getChildList();

        if (! isset($children[$name])) {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'docman_item_not_available'));
        }

        if ($children[$name] === self::DUPLICATE) {
            throw new Sabre_DAV_Exception_Conflict($GLOBALS['Language']->getText('plugin_webdav_common', 'docman_item_duplicated'));
        }

        if ($children[$name] === self::ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV) {
            throw new Sabre_DAV_Exception_BadRequest(dgettext('tuleap-webdav', 'Item exists but cannot be displayed over webdav (link, wiki, empty)'));
        }

        return $children[$name];
    }

    /**
     * Returns the name of the folder
     */
    public function getName(): string
    {
        if ($this->isDocmanRoot()) {
            // case of the root
            return 'Documents';
        }
        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getItem()->getTitle());
    }

    public function getLastModified(): int
    {
        return $this->getItem()->getUpdateDate();
    }

    /**
     * @return Docman_Folder
     */
    protected function getItem()
    {
        return $this->item;
    }

    /**
     * @return Project
     */
    protected function getProject()
    {
        return $this->project;
    }

    /**
     * @return PFUser
     */
    protected function getUser()
    {
        return $this->user;
    }

    protected function getUtils(): WebDAVUtils
    {
        if ($this->utils) {
            return $this->utils;
        }
        return WebDAVUtils::getInstance();
    }

    private function isDocmanRoot(): bool
    {
        return ! $this->getItem()->getParentId();
    }

    /**
     * Create a new docman folder
     *
     * @param string $name Name of the folder to create
     */
    public function createDirectory($name): void
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
     * @param string $name Name of the document
     * @param resource $data Content of the document
     */
    public function createFile($name, $data = null): void
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params = [];
            $params['action']   = 'createItem';
            $params['group_id'] = $this->getProject()->getGroupId();
            $params['ordering'] = 'beginning';
            $params['confirm']  = true;

            // File stuff
            $params['file_name']      = $name;
            $params['upload_content'] = $data === null ? '' : stream_get_contents($data);
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
     * @param string $name New name of the folder
     */
    public function setName($name): void
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params = [];
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

    public function delete(): void
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params = [];
            $params['action']   = 'delete';
            $params['group_id'] = $this->getProject()->getGroupId();
            $params['confirm']  = true;
            $params['id']       = $this->getItem()->getId();
            $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }

    public function userCanWrite(): bool
    {
        $docmanPermissionManager = $this->getUtils()->getDocmanPermissionsManager($this->project);
        return $this->getUtils()->isWriteEnabled() && $docmanPermissionManager->userCanWrite($this->user, $this->item->getId());
    }
}
