<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use GuzzleHttp\Psr7\ServerRequest;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class WebDAVFRSFile extends \Sabre\DAV\File
{
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var FRSFile
     */
    private $file;
    /**
     * @var WebDAVUtils
     */
    private $utils;

    public function __construct(PFUser $user, Project $project, FRSFile $file, WebDAVUtils $utils)
    {
        $this->user    = $user;
        $this->project = $project;
        $this->file    = $file;
        $this->utils   = $utils;
    }

    /**
     * This method is used to download the file
     */
    public function get(): void
    {
        // Log the download in the Log system
        $this->file->LogDownload((int) $this->user->getId());

        // Start download
        $response_builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());
        $response         = $response_builder->fromFilePath(
            ServerRequest::fromGlobals(),
            $this->file->getFileLocation(),
            $this->getName(),
            $this->getContentType() ?? 'application/octet-stream'
        )
            ->withHeader('ETag', $this->getETag())
            ->withHeader('Last-Modified', (string) $this->getLastModified());
        (new SapiStreamEmitter())->emit($response);
        exit();
    }

    public function put($data): void
    {
        if (! file_put_contents($this->file->getFileLocation(), $data)) {
            throw new \Sabre\DAV\Exception\Forbidden('Permission denied to change data');
        }

        $frs_file_factory = new FRSFileFactory();
        $frs_file_factory->update([
            'file_id'      => $this->file->getFileId(),
            'file_size'    => filesize($this->file->getFileLocation()),
            'computed_md5' => $this->utils->getIncomingFileMd5Sum($this->file->getFileLocation()),
        ]);
    }

    public function getName(): string
    {
        /* The file name is preceded by its id to keep
         *  the client able to request the file from its id
         */
        return basename($this->file->getFileName());
    }

    public function getLastModified(): int
    {
        return $this->file->getPostDate();
    }

    public function getSize(): int
    {
        return $this->file->getFileSize();
    }

    /**
     * Returns a unique identifier of the file
     */
    public function getETag(): string
    {
        return '"' . $this->utils->getIncomingFileMd5Sum($this->file->getFileLocation()) . '"';
    }

    /**
     * Returns mime-type of the file
     *
     * @return string|null
     *
     * @psalm-suppress ImplementedReturnTypeMismatch Return type of the library is incorrect
     */
    public function getContentType()
    {
        if (file_exists($this->file->getFileLocation()) && filesize($this->file->getFileLocation())) {
            $mime = MIME::instance();
            return $mime->type($this->file->getFileLocation());
        }
        return null;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function delete(): void
    {
        if ($this->utils->userCanWrite($this->user, $this->getProject()->getGroupId())) {
            $result = $this->utils->getFileFactory()->delete_file($this->getProject()->getGroupId(), $this->file->getFileID());
            if ($result == 0) {
                throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
            }
        } else {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }
}
