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

use Sabre\DAV\ICollection;
use Tuleap\WebDAV\Docman\DocumentDownloader;

class WebDAVDocmanFolder implements ICollection
{
    private const DUPLICATE                                 = 'duplicate';
    private const ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV = 'exists-not-displayed';

    public function __construct(
        private PFUser $user,
        private Project $project,
        private Docman_Folder $item,
        private WebDAVUtils $utils,
    ) {
    }

    private function getMaxFileSize(): int
    {
        return (int) ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
    }

    /**
     * Returns the content of the folder
     * including indication about duplicate entries
     */
    public function getChildList(): array
    {
        $children                = [];
        $docmanItemFactory       = $this->utils->getDocmanItemFactory();
        $nodes                   = $docmanItemFactory->getChildrenFromParent($this->item);
        $docmanPermissionManager = $this->utils->getDocmanPermissionsManager($this->project);

        foreach ($nodes as $node) {
            if ($docmanPermissionManager->userCanAccess($this->user, $node->getId())) {
                $class = $node::class;
                switch ($class) {
                    case Docman_File::class:
                        $item = $docmanItemFactory->getItemFromDb($node->getId());
                        assert($item instanceof Docman_File);
                        $version = $item->getCurrentVersion();
                        $this->appendChildren($children, $version->getFilename(), new WebDAVDocmanFile($this->user, $this->project, $item, new DocumentDownloader(), $this->utils));
                        break;
                    case Docman_EmbeddedFile::class:
                        $item = $docmanItemFactory->getItemFromDb($node->getId());
                        assert($item instanceof Docman_EmbeddedFile);
                        $this->appendChildren($children, $node->getTitle(), new WebDAVDocmanFile($this->user, $this->project, $item, new DocumentDownloader(), $this->utils));
                        break;
                    case Docman_Empty::class:
                    case Docman_Wiki::class:
                    case Docman_Link::class:
                        $this->appendChildren($children, $node->getTitle(), self::ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV);
                        break;
                    default:
                        $this->appendChildren($children, $node->getTitle(), new WebDAVDocmanFolder($this->user, $this->project, $node, $this->utils));
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
     * @return \Sabre\DAV\INode[]
     */
    #[\Override]
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
    #[\Override]
    public function getChild($name): \Sabre\DAV\INode
    {
        $name     = $this->utils->retrieveName($name);
        $children = $this->getChildList();

        if (! isset($children[$name])) {
            throw new \Sabre\DAV\Exception\NotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'docman_item_not_available'));
        }

        if ($children[$name] === self::DUPLICATE) {
            throw new \Sabre\DAV\Exception\Conflict($GLOBALS['Language']->getText('plugin_webdav_common', 'docman_item_duplicated'));
        }

        if ($children[$name] === self::ITEM_EXISTS_BUT_NOT_DISPLAYABLE_IN_WEBDAV) {
            throw new \Sabre\DAV\Exception\BadRequest(dgettext('tuleap-webdav', 'Item exists but cannot be displayed over webdav (link, wiki, empty)'));
        }

        return $children[$name];
    }

    /**
     * Returns the name of the folder
     */
    #[\Override]
    public function getName(): string
    {
        if ($this->isDocmanRoot()) {
            // case of the root
            return 'Documents';
        }
        return $this->utils->unconvertHTMLSpecialChars($this->item->getTitle());
    }

    #[\Override]
    public function getLastModified(): int
    {
        return $this->item->getUpdateDate();
    }

    private function isDocmanRoot(): bool
    {
        return ! $this->item->getParentId();
    }

    /**
     * Create a new docman folder
     *
     * @param string $name Name of the folder to create
     */
    #[\Override]
    public function createDirectory($name): void
    {
        if ($this->utils->isWriteEnabled()) {
            // Request
            $params['action']   = 'createItem';
            $params['group_id'] = $this->project->getGroupId();
            $params['ordering'] = 'beginning';
            $params['confirm']  = true;

            // Item details
            $params['item']['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
            $params['item']['parent_id'] = $this->item->getId();
            $params['item']['title']     = $name;

            $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
        } else {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'folder_denied_create'));
        }
    }

    /**
     * Creates a new document under the folder
     *
     * @param string               $name Name of the file
     * @param resource|string|null $data Initial payload
     */
    #[\Override]
    public function createFile($name, $data = null): void
    {
        if ($this->utils->isWriteEnabled()) {
            // Request
            $params           = [];
            $params['action'] = 'createItem';
            if ($this->childExists($name)) {
                $params['action'] = 'new_version';
            }
            $params['group_id'] = $this->project->getGroupId();
            $params['ordering'] = 'beginning';
            $params['confirm']  = true;

            // File stuff
            $params['file_name'] = $name;

            if ($data === null) {
                $params['upload_content'] = '';
            } elseif (is_resource($data)) {
                $params['upload_content'] = stream_get_contents($data);
            } else {
                $params['upload_content'] = $data;
            }
            if (strlen($params['upload_content']) <= $this->getMaxFileSize()) {
                $params['item']['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FILE;
                $params['item']['parent_id'] = $this->item->getId();
                $params['item']['title']     = $name;

                $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
            } else {
                throw new \Sabre\DAV\Exception\RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }
        } else {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_create'));
        }
    }

    /**
     * @param string $name
     */
    #[\Override]
    public function childExists($name): bool
    {
        try {
            $child = $this->getChild($name);
        } catch (\Sabre\DAV\Exception\NotFound $ex) {
            return false;
        } catch (\Sabre\DAV\Exception\Conflict $ex) {
            return true;
        } catch (\Sabre\DAV\Exception\BadRequest $ex) {
            return true;
        }

        return true;
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
    #[\Override]
    public function setName($name): void
    {
        if ($this->utils->isWriteEnabled()) {
            // Request
            $params             = [];
            $params['action']   = 'update';
            $params['group_id'] = $this->project->getGroupId();
            $params['confirm']  = true;

            // Item details
            $params['item']['id']    = $this->item->getId();
            $params['item']['title'] = $name;

            $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
        } else {
            throw new \Sabre\DAV\Exception\MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'folder_denied_rename'));
        }
    }

    #[\Override]
    public function delete(): void
    {
        if ($this->utils->isWriteEnabled()) {
            // Request
            $params             = [];
            $params['action']   = 'delete';
            $params['group_id'] = $this->project->getGroupId();
            $params['confirm']  = true;
            $params['id']       = $this->item->getId();
            $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
        } else {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }

    public function userCanWrite(): bool
    {
        $docmanPermissionManager = $this->utils->getDocmanPermissionsManager($this->project);
        return $this->utils->isWriteEnabled() && $docmanPermissionManager->userCanWrite($this->user, $this->item->getId());
    }
}
