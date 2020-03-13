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

use Tuleap\WebDAV\Docman\DocumentDownloader;

/**
 * This class Represents Docman files & embedded files in WebDAV
 */
class WebDAVDocmanFile extends WebDAVDocmanDocument
{

    public function __construct($user, $project, $item, DocumentDownloader $document_downloader)
    {
        parent::__construct($user, $project, $item, $document_downloader);
    }

    /**
     * This method is used to download the file
     *
     * @return void
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::get()
     */
    public function get()
    {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();

        if (file_exists($version->getPath())) {
            if ($this->getSize() <= $this->getMaxFileSize()) {
                try {
                    $this->download($version);
                } catch (Exception $e) {
                    throw new Sabre_DAV_Exception_FileNotFound($e->getMessage());
                }
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
    public function getName()
    {
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
     * @return string
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::getContentType()
     * @psalm-suppress ImplementedReturnTypeMismatch Return type of the library is incorrect
     */
    public function getContentType()
    {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();
        return $version->getFiletype();
    }

    /**
     * Returns the file size
     *
     * @return int
     *
     * @see plugins/webdav/include/FS/WebDAVDocmanDocument::getSize()
     */
    public function getSize()
    {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();
        return $version->getFilesize();
    }

    /**
     * Returns a unique identifier of the file
     *
     * @return String
     */
    public function getETag()
    {
        $item = $this->getItem();
        $version = $item->getCurrentVersion();
        return '"' . $this->getUtils()->getIncomingFileMd5Sum($version->getPath()) . '"';
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
     * Downloads the file
     *
     * @param Docman_Version $version
     *
     * @return void
     */
    public function download($version, $filesize = '', $path = '')
    {
        $version->preDownload($this->getItem(), $this->getUser());
        // Download the file
        parent::download($version->getFiletype(), $version->getFilesize(), $version->getPath());
    }

    /**
     * Create a new version of the file
     *
     * @param $data
     *
     * @return void
     */
    public function put($data)
    {
        if ($this->getUtils()->isWriteEnabled()) {
            // Request
            $params['action']   = 'new_version';
            $params['group_id'] = $this->getProject()->getID();
            $params['confirm']  = true;

            // File stuff
            $params['id']             = $this->getItem()->getId();
            $params['file_name']      = $this->getName();
            $params['upload_content'] = stream_get_contents($data);
            if (strlen($params['upload_content']) <= $this->getMaxFileSize()) {
                $this->getUtils()->processDocmanRequest(new WebDAV_Request($params));
            } else {
                throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable($GLOBALS['Language']->getText('plugin_webdav_download', 'error_file_size'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_new_version'));
        }
    }

    /**
     * Rename an embedded file
     * We don't allow renaming files
     *
     * Even if rename is forbidden some silly WebDAV clients (ie : Micro$oft's one)
     * will bypass that and try to delete the original file
     * then upload another one with the same content and a new name
     * Which is very different from just renaming the file
     *
     * @param String $name New name of the document
     *
     * @return void
     */
    public function setName($name)
    {
        switch (get_class($this->getItem())) {
            case 'Docman_File':
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_rename'));
                break;
            case 'Docman_EmbeddedFile':
                parent::setName($name);
                break;
        }
    }
}
