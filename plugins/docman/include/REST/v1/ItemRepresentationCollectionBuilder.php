<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\Item\PaginatedDocmanItemCollection;

class ItemRepresentationCollectionBuilder
{
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    /**
     * @var \Docman_PermissionsManager
     */
    private $permission_manager;
    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;
    /**
     * @var \Docman_ItemDao
     */
    private $item_dao;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        \Docman_PermissionsManager $permission_manager,
        ItemRepresentationBuilder $item_representation_builder,
        \Docman_ItemDao $item_dao
    ) {
        $this->item_factory                = $item_factory;
        $this->permission_manager          = $permission_manager;
        $this->item_representation_builder = $item_representation_builder;
        $this->item_dao                    = $item_dao;
    }

    /**
     * @return PaginatedDocmanItemCollection
     */
    public function buildFolderContent(\Docman_Item $item, \PFUser $user, $limit, $offset)
    {
        $dar        = $this->item_dao->searchByParentIdWithPagination($item->getId(), $limit, $offset);
        $row_number = $this->item_dao->foundRows();

        $children = [];
        foreach ($dar as $row) {
            if ($row && $this->permission_manager->userCanRead($user, $row['item_id'])) {
                $docman_item = $this->item_factory->getItemFromRow($row);
                $children[]  = $this->item_representation_builder->buildItemRepresentation($docman_item);
            }
        }

        $paginated_children = new PaginatedDocmanItemCollection($children, $row_number);
        return $paginated_children;
    }
}
