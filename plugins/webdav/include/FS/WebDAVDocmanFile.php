<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Sabre\DAV\IFile;
use Tuleap\WebDAV\Docman\DocumentDownloader;

/**
 * This class Represents Docman files & embedded files in WebDAV
 */
class WebDAVDocmanFile implements IFile
{
    public function __construct(
        private PFUser $user,
        private Project $project,
        private Docman_File $item,
        private DocumentDownloader $document_downloader,
        private WebDAVUtils $utils,
    ) {
    }

    /**
     * This method is used to download the file
     */
    #[\Override]
    public function get(): void
    {
        $version = $this->item->getCurrentVersion();

        if (file_exists($version->getPath())) {
            if ($this->getSize() <= $this->getMaxFileSize()) {
                try {
                    $this->download($version);
                } catch (Exception $e) {
                    throw new \Sabre\DAV\Exception\NotFound($e->getMessage());
                }
            } else {
                throw new \Sabre\DAV\Exception\RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }
        } else {
            throw new \Sabre\DAV\Exception\NotFound($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
        }
    }

    /**
     * Returns the name of the file
     */
    #[\Override]
    public function getName(): string
    {
        switch (get_class($this->item)) {
            case Docman_File::class:
                $version = $this->item->getCurrentVersion();
                return $version->getFilename();
            case Docman_EmbeddedFile::class:
                return $this->item->getTitle();
        }
        throw new RuntimeException('Invalid Item type in ' . self::class . ': ' . get_class($this->item));
    }

    /**
     * Returns mime-type of the file
     *
     * @psalm-suppress ImplementedReturnTypeMismatch Return type of the library is incorrect
     */
    #[\Override]
    public function getContentType(): string
    {
        $version = $this->item->getCurrentVersion();
        return $version->getFiletype();
    }

    /**
     * Returns the file size
     */
    #[\Override]
    public function getSize(): int
    {
        $version = $this->item->getCurrentVersion();
        return $version->getFilesize();
    }

    /**
     * Returns a unique identifier of the file
     */
    #[\Override]
    public function getETag(): string
    {
        $version = $this->item->getCurrentVersion();
        return '"' . $this->utils->getIncomingFileMd5Sum($version->getPath()) . '"';
    }

    public function getMaxFileSize(): int
    {
        return (int) ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
    }

    #[\Override]
    public function getLastModified(): int
    {
        return $this->item->getUpdateDate();
    }

    #[\Override]
    public function delete(): void
    {
        if ($this->utils->isWriteEnabled()) {
            try {
                // Request
                $params['action']   = 'delete';
                $params['group_id'] = $this->project->getID();
                $params['confirm']  = true;
                $params['id']       = $this->item->getId();
                $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
            } catch (Exception $e) {
                throw new \Sabre\DAV\Exception\MethodNotAllowed($e->getMessage());
            }
        } else {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }

    private function download(Docman_Version $version): void
    {
        $version->preDownload($this->item, $this->user);
        // Download the file
        $this->document_downloader->downloadDocument($this->getName(), $version->getFiletype(), $version->getFilesize(), $version->getPath());
    }

    /**
     * Create a new version of the file
     *
     * @param string|resource $data
     */
    #[\Override]
    public function put($data): void
    {
        if ($this->utils->isWriteEnabled()) {
            // Request
            $params             = [];
            $params['action']   = 'new_version';
            $params['group_id'] = $this->project->getID();
            $params['confirm']  = true;

            // File stuff
            $params['id']        = $this->item->getId();
            $params['file_name'] = $this->getName();
            if (is_resource($data)) {
                $params['upload_content'] = stream_get_contents($data);
            } else {
                $params['upload_content'] = $data;
            }
            if (strlen($params['upload_content']) <= $this->getMaxFileSize()) {
                $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
            } else {
                throw new \Sabre\DAV\Exception\RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }
        } else {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_new_version'));
        }
    }

    /**
     * Rename an embedded file
     *
     * We don't allow renaming files
     *
     * Even if rename is forbidden some silly WebDAV clients (ie : Micro$oft's one)
     * will bypass that and try to delete the original file
     * then upload another one with the same content and a new name
     * Which is very different from just renaming the file
     *
     * @param string $name New name of the document
     */
    #[\Override]
    public function setName($name): void
    {
        switch (get_class($this->item)) {
            case Docman_File::class:
                throw new \Sabre\DAV\Exception\MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_rename'));
            case Docman_EmbeddedFile::class:
                $this->rename($name);
                break;
        }
    }

    private function rename(string $name): void
    {
        if ($this->utils->isWriteEnabled()) {
            try {
                // Request
                $params             = [];
                $params['action']   = 'update';
                $params['group_id'] = $this->project->getID();
                $params['confirm']  = true;

                // Item details
                $params['item']['id']    = $this->item->getId();
                $params['item']['title'] = $name;

                $this->utils->processDocmanRequest(new WebDAV_Request($params), $this->user);
            } catch (Exception $e) {
                throw new \Sabre\DAV\Exception\MethodNotAllowed($e->getMessage());
            }
        } else {
            throw new \Sabre\DAV\Exception\MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_rename'));
        }
    }
}
