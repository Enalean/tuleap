<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved.
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

use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OtherDocument;

/**
 * @template-implements ItemVisitor<string>
 */
class Docman_View_GetShowViewVisitor implements ItemVisitor
{
    #[\Override]
    public function visitFolder(Docman_Folder $item, $params = [])
    {
        return (string) Docman_View_Browse::getViewForCurrentUser($item->getGroupId(), $params);
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = [])
    {
        return 'Redirect';
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, $params = [])
    {
        return 'Redirect';
    }

    #[\Override]
    public function visitLink(Docman_Link $item, $params = [])
    {
        return 'Redirect';
    }

    #[\Override]
    public function visitFile(Docman_File $item, $params = [])
    {
        return 'Download';
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = [])
    {
        return 'Embedded';
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, $params = [])
    {
        return 'Empty';
    }

    #[\Override]
    public function visitItem(Docman_Item $item, $params = [])
    {
        return '';
    }
}
