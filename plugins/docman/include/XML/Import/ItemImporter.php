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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Docman_ItemFactory;
use Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException;

class ItemImporter
{
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var PermissionsImporter
     */
    private $permissions_importer;

    public function __construct(
        PermissionsImporter $permissions_importer,
        Docman_ItemFactory $item_factory
    ) {
        $this->item_factory         = $item_factory;
        $this->permissions_importer = $permissions_importer;
    }

    /**
     * @throws CannotInstantiateItemWeHaveJustCreatedInDBException
     */
    public function import(
        \SimpleXMLElement $node,
        NodeImporter $node_importer,
        PostImporter $post_importer,
        Docman_Item $parent_item,
        ImportProperties $properties
    ): void {
        $item = $this->item_factory->createWithoutOrdering(
            $properties->getTitle(),
            $properties->getDescription(),
            $parent_item->getId(),
            PLUGIN_DOCMAN_ITEM_STATUS_NONE,
            0,
            $properties->getOwner()->getId(),
            $properties->getItemTypeId(),
            $properties->getCreateDate(),
            $properties->getUpdateDate(),
            null,
            $properties->getLinkUrl()
        );
        $this->permissions_importer->importPermissions($parent_item, $item, $node);
        $post_importer->postImport($node_importer, $node, $item);
    }
}
