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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Docman\REST\v1;

use Codendi_HTMLPurifier;
use Docman_ItemDao;
use Docman_ItemFactory;
use Project;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\REST\v1\EmbeddedFiles\IEmbeddedFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Docman\REST\v1\Metadata\UnknownMetadataException;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsBuilder;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
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
     * @var MetadataRepresentationBuilder
     */
    private $metadata_representation_builder;
    /**
     * @var ApprovalTableStateMapper
     */
    private $approval_table_state_mapper;
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;
    /**
     * @var DocmanItemPermissionsForGroupsBuilder
     */
    private $item_permissions_for_groups_builder;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(
        Docman_ItemDao $dao,
        \UserManager $user_manager,
        Docman_ItemFactory $docman_item_factory,
        \Docman_PermissionsManager $permissions_manager,
        \Docman_LockFactory $lock_factory,
        ApprovalTableStateMapper $approval_table_state_mapper,
        MetadataRepresentationBuilder $metadata_representation_builder,
        ApprovalTableRetriever $approval_table_retriever,
        DocmanItemPermissionsForGroupsBuilder $item_permissions_for_groups_builder,
        Codendi_HTMLPurifier $purifier
    ) {
        $this->dao                                 = $dao;
        $this->user_manager                        = $user_manager;
        $this->docman_item_factory                 = $docman_item_factory;
        $this->permissions_manager                 = $permissions_manager;
        $this->lock_factory                        = $lock_factory;
        $this->approval_table_state_mapper         = $approval_table_state_mapper;
        $this->metadata_representation_builder     = $metadata_representation_builder;
        $this->approval_table_retriever            = $approval_table_retriever;
        $this->purifier                            = $purifier;
        $this->item_permissions_for_groups_builder = $item_permissions_for_groups_builder;
    }

    /**
     * @throws UnknownMetadataException
     */
    public function buildRootId(Project $project, \PFUser $current_user) : ?ItemRepresentation
    {
        $result = $this->dao->searchRootItemForGroupId($project->getID());

        if (! $result) {
            return null;
        }

        $item = $this->docman_item_factory->getItemFromRow($result);
        if ($item === null || ! $this->permissions_manager->userCanRead($current_user, $item->getId())) {
            return null;
        }

        return $this->buildItemRepresentation(
            $item,
            $current_user,
            ItemRepresentation::TYPE_FOLDER
        );
    }

    /**
     * @return ItemRepresentation
     * @throws UnknownMetadataException
     */
    public function buildItemRepresentation(
        \Docman_Item $item,
        \PFUser $current_user,
        ?string $type,
        ?FilePropertiesRepresentation $file_properties = null,
        ?IEmbeddedFilePropertiesRepresentation $embedded_file_properties = null,
        ?LinkPropertiesRepresentation $link_properties = null,
        ?WikiPropertiesRepresentation $wiki_properties = null
    ) {
        $owner                = $this->user_manager->getUserById($item->getOwnerId());
        $owner_representation = new MinimalUserRepresentation();
        $owner_representation->build($owner);

        $is_expanded = false;
        if ($type === ItemRepresentation::TYPE_FOLDER) {
            $preference  = $current_user->getPreference("plugin_docman_hide_" . $item->getGroupId() . "_" . $item->getId());
            $is_expanded = $preference !== false;
        }

        $user_can_write      = $this->permissions_manager->userCanWrite($current_user, $item->getId());
        $can_user_manage     = $this->permissions_manager->userCanManage($current_user, $item->getId());
        $item_representation = new ItemRepresentation();

        $lock_info                 = $this->getLockInformation($item);
        $approval_table            = $this->getApprovalTable($item);
        $has_approval_item         = $this->approval_table_retriever->hasApprovalTable($item);
        $is_approval_table_enabled = $approval_table !== null;

        $metadata_representations = $this->metadata_representation_builder->build($item);

        $item_representation->build(
            $item,
            $this->purifier,
            $owner_representation,
            $user_can_write,
            $type,
            $is_expanded,
            $can_user_manage,
            $metadata_representations,
            $has_approval_item,
            $is_approval_table_enabled,
            $approval_table,
            $lock_info,
            $this->item_permissions_for_groups_builder->getRepresentation($current_user, $item),
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
        $approval_table = $this->approval_table_retriever->retrieveByItem($item);
        if (! $approval_table) {
            return null;
        }

        $table_owner    = $this->getMinimalUserRepresentation((int) $approval_table->getOwner());

        return new ItemApprovalTableRepresentation(
            $approval_table,
            $table_owner,
            $this->approval_table_state_mapper
        );
    }

    private function getMinimalUserRepresentation(int $user_id) : MinimalUserRepresentation
    {
        return (new MinimalUserRepresentation())->build(
            $this->user_manager->getUserById($user_id)
        );
    }
}
