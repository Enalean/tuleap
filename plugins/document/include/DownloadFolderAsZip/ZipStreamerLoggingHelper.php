<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Document\DownloadFolderAsZip;

use BackendLogger;

class ZipStreamerLoggingHelper
{
    private const LOGGER_NAME = 'document-zip-folder';

    /**
     * @var \WrapperLogger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new \WrapperLogger(
            BackendLogger::getDefaultLogger(),
            self::LOGGER_NAME
        );
    }

    public function logOverflowExceptionError(\Docman_Folder $folder): void
    {
        $this->logger->error(
            sprintf(
                'Overflow error occurred during the generation of the archive of the folder "%s" (id: %d)',
                $folder->getTitle(),
                $folder->getId()
            )
        );
    }

    public function logFileNotFoundException(\Docman_Item $item, string $fs_path): void
    {
        $this->logger->error(
            sprintf(
                'File not found: %s (id: %d) not found in path %s',
                $item->getTitle(),
                $item->getId(),
                $fs_path
            )
        );
    }

    public function logFileNotReadableException(\Docman_Item $item, string $fs_path): void
    {
        $this->logger->error(
            sprintf(
                'File not readable: %s (id: %d), path %s',
                $item->getTitle(),
                $item->getId(),
                $fs_path
            )
        );
    }

    public function logCorruptedFile(\Docman_File $item): void
    {
        $this->logger->error(
            sprintf(
                'File corrupted, no current version found: %s (id: %d)',
                $item->getTitle(),
                $item->getId()
            )
        );
    }
}
