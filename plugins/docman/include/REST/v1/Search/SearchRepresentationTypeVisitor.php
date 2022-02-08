<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\REST\v1\ItemRepresentation;

/**
 * @template-implements ItemVisitor<string | null>
 */
final class SearchRepresentationTypeVisitor implements ItemVisitor
{
    public function visitFolder(\Docman_Folder $item, array $params = [])
    {
        return ItemRepresentation::TYPE_FOLDER;
    }

    public function visitWiki(\Docman_Wiki $item, array $params = [])
    {
        return ItemRepresentation::TYPE_WIKI;
    }

    public function visitLink(\Docman_Link $item, array $params = [])
    {
        return ItemRepresentation::TYPE_LINK;
    }

    public function visitFile(\Docman_File $item, array $params = [])
    {
        return ItemRepresentation::TYPE_FILE;
    }

    public function visitEmbeddedFile(\Docman_EmbeddedFile $item, array $params = [])
    {
        return ItemRepresentation::TYPE_EMBEDDED;
    }

    public function visitEmpty(\Docman_Empty $item, array $params = [])
    {
        return ItemRepresentation::TYPE_EMPTY;
    }

    public function visitItem(\Docman_Item $item, array $params = [])
    {
        return null;
    }
}
