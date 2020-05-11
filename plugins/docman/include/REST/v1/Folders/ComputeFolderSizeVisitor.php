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

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;

class ComputeFolderSizeVisitor implements ItemVisitor
{
    public function visitFolder(Docman_Folder $item, array $params = []): void
    {
        $items = $item->getAllItems();
        $iterator = $items->iterator();

        $params['size_collector']->addOneFolder();

        while ($iterator->valid()) {
            $current_item = $iterator->current();
            $current_item->accept(
                $this,
                $params
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
        $this->addFileSizeIfFileIsNotCorrupted($item, $params['size_collector']);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): void
    {
        $this->addFileSizeIfFileIsNotCorrupted($item, $params['size_collector']);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): void
    {
    }

    public function visitItem(Docman_Item $item, array $params = []): void
    {
    }

    private function isFileVersionCorrupted(?\Docman_Version $version): bool
    {
        return $version === null;
    }

    private function addFileSizeIfFileIsNotCorrupted(Docman_File $item, FolderSizeCollector $size_collector): void
    {
        $current_version = $item->getCurrentVersion();

        if ($this->isFileVersionCorrupted($current_version)) {
            return;
        }

        $size_collector->addFileSize(
            $current_version->getFilesize()
        );
    }
}
