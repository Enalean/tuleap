<?php
/**
 * Copyright (c) Enalean, 2019-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OtherDocument;

/**
 * @template-implements ItemVisitor<?int>
 */
class Docman_View_ToolbarNewDocumentVisitor implements ItemVisitor
{
    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = []): ?int
    {
        return $item->getId();
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): ?int
    {
        return $item->getParentId();
    }

    public function visitDocument(Docman_Document $item, array $params = []): ?int
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = []): ?int
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitLink(Docman_Link $item, array $params = []): ?int
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitFile(Docman_File $item, array $params = []): ?int
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): ?int
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = []): ?int
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = []): ?int
    {
        return $this->visitDocument($item, $params);
    }
}
