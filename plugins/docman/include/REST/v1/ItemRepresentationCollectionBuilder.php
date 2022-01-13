<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use Tuleap\Docman\Item\PaginatedParentRowCollection;
use Tuleap\Docman\REST\v1\Folders\ParentFolderRepresentation;

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
     * @var \Docman_ItemDao
     */
    private $item_dao;
    /**
     * @var ItemRepresentationVisitor
     */
    private $item_representation_visitor;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        \Docman_PermissionsManager $permission_manager,
        ItemRepresentationVisitor $item_representation_visitor,
        \Docman_ItemDao $item_dao,
    ) {
        $this->item_factory                = $item_factory;
        $this->permission_manager          = $permission_manager;
        $this->item_dao                    = $item_dao;
        $this->item_representation_visitor = $item_representation_visitor;
    }

    /**
     * @return PaginatedDocmanItemCollection
     */
    public function buildFolderContent(\Docman_Item $item, \PFUser $user, $limit, $offset)
    {
        $dar        = $this->item_dao->searchByParentIdWithPagination($item->getId(), $limit, $offset);
        $row_number = $this->item_dao->foundRows();
        $children   = [];
        foreach ($dar as $row) {
            if ($row && $this->permission_manager->userCanRead($user, $row['item_id'])) {
                $docman_item = $this->item_factory->getItemFromRow($row);
                $children[]  = $docman_item->accept($this->item_representation_visitor, ['current_user' => $user, 'is_a_direct_access' => false]);
            }
        }

        $paginated_children = new PaginatedDocmanItemCollection($children, $row_number);
        return $paginated_children;
    }

    /**
     * @return PaginatedDocmanItemCollection
     * @throws \Tuleap\Request\ForbiddenException
     */
    public function buildParentsItemRepresentation(\Docman_Item $item, \PFUser $user, $limit, $offset)
    {
        $parents = [];

        $this->buildParentCollection($item, $user, $parents, $limit, $offset);
        $representations = [];
        foreach ($parents as $parent) {
            $representations[] = $parent->accept($this->item_representation_visitor, ['current_user' => $user]);
        }

        return new PaginatedDocmanItemCollection(array_slice($representations, $offset, $limit), count($representations));
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     */
    public function buildParentRowCollection(
        \Docman_Item $item,
        \PFUser $user,
        $limit,
        $offset,
    ): PaginatedParentRowCollection {
        $parents = [];

        $this->buildParentCollection($item, $user, $parents, $limit, $offset);

        $representations = [];
        foreach ($parents as $parent) {
            $representations[] = ParentFolderRepresentation::build($parent);
        }

        return new PaginatedParentRowCollection(array_slice($representations, $offset, $limit), count($representations));
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     */
    private function buildParentCollection(
        \Docman_Item $item,
        \PFUser $user,
        array &$parents,
        int $limit,
        int $offset,
    ): void {
        if ($item->getParentId() === 0) {
            return;
        }
        if (! $this->permission_manager->userCanRead($user, $item->getId())) {
            throw new \Tuleap\Request\ForbiddenException();
        }

        $parent = $this->item_factory->getItemFromDb($item->getParentId());
        if (! $parent) {
            return;
        }

        $this->buildParentCollection($parent, $user, $parents, $limit, $offset);
        $parents[] = $parent;
    }
}
