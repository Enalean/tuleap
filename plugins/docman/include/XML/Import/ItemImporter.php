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
use PFUser;
use Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException;

class ItemImporter
{
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var \PermissionsManager
     */
    private $permission_manager;

    public function __construct(
        \PermissionsManager $permission_manager,
        Docman_ItemFactory $item_factory
    ) {
        $this->item_factory       = $item_factory;
        $this->permission_manager = $permission_manager;
    }

    /**
     * @throws CannotInstantiateItemWeHaveJustCreatedInDBException
     */
    public function import(
        \SimpleXMLElement $node,
        NodeImporter $node_importer,
        PostImporter $post_importer,
        Docman_Item $parent_item,
        PFUser $user,
        ImportProperties $properties
    ): void {
        $item = $this->item_factory->createWithoutOrdering(
            $properties->getTitle(),
            '',
            $parent_item->getId(),
            PLUGIN_DOCMAN_ITEM_STATUS_NONE,
            0,
            $user->getId(),
            $properties->getItemTypeId(),
            $properties->getWikiPage(),
            $properties->getLinkUrl()
        );
        $this->clonePermissions($parent_item, $item);
        $post_importer->postImport($node_importer, $node, $item, $user);
    }

    private function clonePermissions(Docman_Item $parent_item, Docman_Item $item): void
    {
        $this->permission_manager->clonePermissions(
            $parent_item->getId(),
            $item->getId(),
            ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
        );
    }
}
