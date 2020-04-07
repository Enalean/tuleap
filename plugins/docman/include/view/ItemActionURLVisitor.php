<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\View;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;

final class ItemActionURLVisitor implements ItemVisitor
{
    public function visitFolder(Docman_Folder $item, array $params = []): ?string
    {
        return null;
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): ?string
    {
        return null;
    }

    public function visitLink(Docman_Link $item, array $params = []): ?string
    {
        return null;
    }

    public function visitFile(Docman_File $item, array $params = []): ?string
    {
        if (! isset($params['action']) || $params['action'] !== 'show') {
            return null;
        }
        $download_href = '/plugins/docman/download/' . urlencode((string) $item->getId());
        if (isset($params['version_number'])) {
            $download_href .= '/' . urlencode((string) $params['version_number']);
        }
        return $download_href;
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): ?string
    {
        return null;
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): ?string
    {
        return null;
    }

    public function visitItem(Docman_Item $item, array $params = []): ?string
    {
        return null;
    }
}
