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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Docman\REST\v1;

use Docman_ItemDao;
use Docman_ItemFactory;
use Project;
use Tuleap\User\REST\MinimalUserRepresentation;

class ItemRepresentationBuilder
{
    /**
     * @var Docman_ItemDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var \Docman_PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        Docman_ItemDao $dao,
        \UserManager $user_manager,
        Docman_ItemFactory $docman_item_factory,
        \Docman_PermissionsManager $permissions_manager
    ) {
        $this->dao                 = $dao;
        $this->user_manager        = $user_manager;
        $this->docman_item_factory = $docman_item_factory;
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @param Project $project
     *
     * @return ItemRepresentation|null
     */
    public function buildRootId(Project $project, \PFUser $current_user)
    {
        $result = $this->dao->searchRootItemForGroupId($project->getID());

        if (! $result) {
            return;
        }

        $item = $this->docman_item_factory->getItemFromRow($result);
        if (! $item) {
            return;
        }

        return $this->buildItemRepresentation(
            $item,
            $current_user,
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            null
        );
    }

    /**
     * @return ItemRepresentation
     */
    public function buildItemRepresentation(
        \Docman_Item $item,
        \PFUser $current_user,
        $type,
        FilePropertiesRepresentation $file_properties = null,
        EmbeddedFilePropertiesRepresentation $embedded_file_properties = null,
        LinkPropertiesRepresentation $link_properties = null
    ) {
        $owner               = $this->user_manager->getUserById($item->getOwnerId());
        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($owner);

        $user_can_write = $this->permissions_manager->userCanWrite($current_user, $item->getId());

        $item_representation = new ItemRepresentation();
        $item_representation->build(
            $item,
            $user_representation,
            $user_can_write,
            $type,
            $file_properties,
            $embedded_file_properties,
            $link_properties
        );

        return $item_representation;
    }
}
