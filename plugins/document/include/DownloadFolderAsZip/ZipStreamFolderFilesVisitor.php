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
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;
use ZipStream\ZipStream;

final class ZipStreamFolderFilesVisitor implements ItemVisitor
{
    /**
     * @var ZipStream
     */
    private $zip;

    public function __construct(ZipStream $zip)
    {
        $this->zip = $zip;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): void
    {
        $items = $item->getAllItems();
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
        $fs_path       = $item->getCurrentVersion()->getPath();
        $name          = $item->getTitle();
        $document_path = $params['path'];

        $this->zip->addFileFromPath($document_path . '/' . $name, $fs_path);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): void
    {
        $fs_path       = $item->getCurrentVersion()->getPath();
        $name          = $item->getTitle();
        $document_path = $params['path'];

        $this->zip->addFileFromPath($document_path . '/' . $name . '.html', $fs_path);
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
}
