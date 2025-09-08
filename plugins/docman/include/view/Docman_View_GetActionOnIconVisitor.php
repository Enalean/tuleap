<?php
/**
 * Copyright (c) Enalean, 2019-Present. All rights reserved
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * @template-implements ItemVisitor<string>
 */
class Docman_View_GetActionOnIconVisitor implements ItemVisitor
{
    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = []): string
    {
        return $params['view']->getActionOnIconForFolder($item, $params);
    }

    public function visitDocument(Docman_Document $item, array $params = []): string
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): string
    {
        return 'show';
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitLink(Docman_Link $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitFile(Docman_File $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    #[\Override]
    public function visitOtherDocument(\Tuleap\Docman\Item\OtherDocument $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }
}
