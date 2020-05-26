<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Tuleap\Docman\REST\v1\Folders\ComputeFolderSizeVisitor;
use Tuleap\Docman\REST\v1\Folders\FolderSizeCollector;
use Tuleap\Document\Config\FileDownloadLimits;

class FolderSizeIsAllowedChecker
{
    /**
     * @var ComputeFolderSizeVisitor
     */
    private $folder_size_visitor;

    private const ONE_MEGABYTE_IN_BYTES = 1000000;

    public function __construct(ComputeFolderSizeVisitor $folder_size_visitor)
    {
        $this->folder_size_visitor = $folder_size_visitor;
    }

    public function checkFolderSizeIsBelowLimit(\Docman_Folder $folder, FileDownloadLimits $limits): bool
    {
        $collector = new FolderSizeCollector();
        $this->folder_size_visitor->visitFolder($folder, ['size_collector' => $collector]);

        // Limits are stored in Megabytes, but total size is in bytes
        return $collector->getTotalSize() <= ($limits->getMaxArchiveSize() * self::ONE_MEGABYTE_IN_BYTES);
    }
}
