<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\Document\DownloadFolderAsZip;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Version;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Document\Exceptions\DocmanFileCorruptedException;
use ZipStream\Exception\FileNotFoundException;
use ZipStream\Exception\FileNotReadableException;
use ZipStream\ZipStream;

final class ZipStreamFolderFilesVisitor implements ItemVisitor
{
    /**
     * @var ZipStream
     */
    private $zip;
    /**
     * @var ZipStreamerLoggingHelper
     */
    private $error_logging_helper;
    /**
     * @var ErrorsListingBuilder
     */
    private $errors_listing_builder;

    public function __construct(
        ZipStream $zip,
        ZipStreamerLoggingHelper $error_logging_helper,
        ErrorsListingBuilder $errors_listing_builder
    ) {
        $this->zip                    = $zip;
        $this->error_logging_helper   = $error_logging_helper;
        $this->errors_listing_builder = $errors_listing_builder;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): void
    {
        $items    = $item->getAllItems();
        $iterator = $items->iterator();

        while ($iterator->valid()) {
            $current_item = $iterator->current();
            $current_item->accept(
                $this,
                $this->getParamsWithCurrentPathUpdated($item, $params)
            );

            $iterator->next();
        }
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): void
    {
    }

    public function visitLink(Docman_Link $item, array $params = []): void
    {
    }

    public function visitFile(Docman_File $item, array $params = []): void
    {
        try {
            $current_version = $this->getFileCurrentVersion($item);
            $name            = $current_version->getFilename();
            $document_path   = $params['path'];

            $this->addFileToArchive($item, $document_path . '/' . $name);
        } catch (DocmanFileCorruptedException $exception) {
            $this->error_logging_helper->logCorruptedFile($item);
            $this->errors_listing_builder->addBadFilePath((string) $item->getTitle());
        }
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): void
    {
        $name          = $item->getTitle();
        $document_path = $params['path'];

        $this->addFileToArchive($item, $document_path . '/' . $name . '.html');
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): void
    {
    }

    public function visitItem(Docman_Item $item, array $params = []): void
    {
    }

    private function isTheBaseFolder(Docman_Folder $item, array $params): bool
    {
        return isset($params['base_folder_id']) && $params['base_folder_id'] === $item->getId();
    }

    private function getParamsWithCurrentPathUpdated(Docman_Folder $item, array $params): array
    {
        if (! $this->isTheBaseFolder($item, $params)) {
            $params['path'] .= '/' . $item->getTitle();
            unset($params['base_folder_id']);
        }

        return $params;
    }

    private function addFileToArchive(Docman_File $item, string $name): void
    {
        $fs_path = '';

        try {
            $fs_path = $this->getPathOnFileSystem($item);
            $this->zip->addFileFromPath(
                $name,
                $fs_path
            );
        } catch (FileNotFoundException $e) {
            $this->error_logging_helper->logFileNotFoundException($item, $fs_path);
            $this->errors_listing_builder->addBadFilePath($name);
        } catch (FileNotReadableException $e) {
            $this->error_logging_helper->logFileNotReadableException($item, $fs_path);
            $this->errors_listing_builder->addBadFilePath($name);
        } catch (DocmanFileCorruptedException $e) {
            $this->error_logging_helper->logCorruptedFile($item);
            $this->errors_listing_builder->addBadFilePath($name);
        }
    }

    /**
     * @throws DocmanFileCorruptedException
     */
    private function getPathOnFileSystem(Docman_File $item): string
    {
        $current_version = $this->getFileCurrentVersion($item);

        return $current_version->getPath();
    }

    /**
     * @throws DocmanFileCorruptedException
     */
    private function getFileCurrentVersion(Docman_File $item): Docman_Version
    {
        $current_version = $item->getCurrentVersion();

        if ($current_version === null) {
            throw new DocmanFileCorruptedException();
        }

        return $current_version;
    }
}
