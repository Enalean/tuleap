<?php
/**
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
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
    /**
     * @var \Docman_LockFactory
     */
    private $lock_factory;

    /**
     * @var \Docman_ApprovalTableFactoriesFactory
     */
    private $approval_table_factory;

    /**
     * @var ApprovalTableStateMapper
     */
    private $approval_table_state_mapper;

    public function __construct(
        Docman_ItemDao $dao,
        \UserManager $user_manager,
        Docman_ItemFactory $docman_item_factory,
        \Docman_PermissionsManager $permissions_manager,
        \Docman_LockFactory $lock_factory,
        \Docman_ApprovalTableFactoriesFactory $approval_table_factory,
        ApprovalTableStateMapper $approval_table_state_mapper
    ) {
        $this->dao                         = $dao;
        $this->user_manager                = $user_manager;
        $this->docman_item_factory         = $docman_item_factory;
        $this->permissions_manager         = $permissions_manager;
        $this->lock_factory                = $lock_factory;
        $this->approval_table_factory      = $approval_table_factory;
        $this->approval_table_state_mapper = $approval_table_state_mapper;
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
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
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
        LinkPropertiesRepresentation $link_properties = null,
        WikiPropertiesRepresentation $wiki_properties = null
    ) {
        $owner               = $this->user_manager->getUserById($item->getOwnerId());
        $owner_representation = new MinimalUserRepresentation();
        $owner_representation->build($owner);

        $is_expanded = false;
        if ($type === ItemRepresentation::TYPE_FOLDER) {
            $preference = $owner->getPreference("plugin_docman_hide_".$item->getGroupId(). "_" .$item->getId());
            $is_expanded = $preference !== false;
        }

        $user_can_write = $this->permissions_manager->userCanWrite($current_user, $item->getId());
        $item_representation = new ItemRepresentation();

        $lock_info = $this->getLockInformation($item);
        $approval_table = $this->getApprovalTable($item);

        $item_representation->build(
            $item,
            $owner_representation,
            $user_can_write,
            $type,
            $is_expanded,
            $approval_table,
            $lock_info,
            $file_properties,
            $embedded_file_properties,
            $link_properties,
            $wiki_properties
        );

        return $item_representation;
    }

    private function getLockInformation(\Docman_Item $item) : ?ItemLockInfoRepresentation
    {
        $lock_infos = $this->lock_factory->getLockInfoForItem($item);

        if (!$lock_infos) {
            return null;
        }

        $lock_owner = $this->getMinimalUserRepresentation((int) $lock_infos['user_id']);

        return new ItemLockInfoRepresentation(
            $lock_owner,
            $lock_infos
        );
    }

    private function getApprovalTable(\Docman_Item $item) : ?ItemApprovalTableRepresentation
    {
        $table_factory = $this->approval_table_factory->getSpecificFactoryFromItem($item);
        if (! $table_factory) {
            return null;
        }

        /* @var $approval_table \Docman_ApprovalTable */
        $approval_table = $table_factory->getTable();
        if (! $approval_table || ! $approval_table->isEnabled()) {
            return null;
        }

        $table_owner = $this->getMinimalUserRepresentation((int) $approval_table->getOwner());

        return new ItemApprovalTableRepresentation($approval_table, $table_owner, $this->approval_table_state_mapper);
    }

    private function getMinimalUserRepresentation(int $user_id) : MinimalUserRepresentation
    {
        return (new MinimalUserRepresentation())->build(
            $this->user_manager->getUserById($user_id)
        );
    }
}
