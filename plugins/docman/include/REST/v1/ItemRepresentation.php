<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
use Tuleap\Docman\REST\v1\EmbeddedFiles\IEmbeddedFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Folders\FolderPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
class ItemRepresentation
{
    public const TYPE_FOLDER   = 'folder';
    public const TYPE_FILE   = 'file';
    public const TYPE_LINK   = 'link';
    public const TYPE_EMBEDDED = 'embedded';
    public const TYPE_WIKI   = 'wiki';
    public const TYPE_EMPTY  = 'empty';

    public const OBSOLESCENCE_DATE_NONE = null;
    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $title;

    /**
     * @var string | null {@type string}
     */
    public $description;

    /**
     * @var string
     */
    public $post_processed_description;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $owner;

    /**
     * @var string | null {@type string}
     */
    public $last_update_date;

    /**
     * @var string | null {@type string}
     */
    public $creation_date;

    /**
     * @var bool {@type bool}
     */
    public $user_can_write;

    /**
     * @var string | null
     */
    public $type;

    /**
     * @var FilePropertiesRepresentation | null
     */
    public $file_properties;

    /**
     * @var IEmbeddedFilePropertiesRepresentation
     */
    public $embedded_file_properties;

    /**
     * @var LinkPropertiesRepresentation | null
     */
    public $link_properties;

    /**
     * @var WikiPropertiesRepresentation | null
     */
    public $wiki_properties;

    /**
    * @var int {@type int}
    */
    public $parent_id;
    /**
     * @var bool {@type bool}
     */
    public $is_expanded;

    /**
     * @var bool {@type bool}
     */
    public $can_user_manage;

    /**
     * @var ItemLockInfoRepresentation | null
     */
    public $lock_info;
    /**
     * @var ItemMetadataRepresentation[]
     */
    public $metadata;

    /**
     * @var bool {@type bool}
     */
    public $has_approval_table;

    /**
     * @var bool {@type bool}
     */
    public $is_approval_table_enabled;

    /**
     * @var ItemApprovalTableRepresentation | null
     */
    public $approval_table;

    /**
     * @var DocmanItemPermissionsForGroupsRepresentation {@required false} {@type \Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsRepresentation}
     * @psalm-var DocmanItemPermissionsForGroupsRepresentation|null
     */
    public $permissions_for_groups;

    /**
     * @var FolderPropertiesRepresentation | null
     */
    public $folder_properties;

    /**
     * @param ItemMetadataRepresentation[] $metadata
     */
    private function __construct(
        int $id,
        string $title,
        ?string $description,
        string $post_processed_description,
        MinimalUserRepresentation $owner,
        ?string $last_update_date,
        ?string $creation_date,
        bool $user_can_write,
        bool $can_user_manage,
        ?string $type,
        ?FilePropertiesRepresentation $file_properties,
        ?IEmbeddedFilePropertiesRepresentation $embedded_file_properties,
        ?LinkPropertiesRepresentation $link_properties,
        ?WikiPropertiesRepresentation $wiki_properties,
        ?FolderPropertiesRepresentation $folder_properties,
        bool $is_expanded,
        ?ItemApprovalTableRepresentation $approval_table,
        ?ItemLockInfoRepresentation $lock_info,
        array $metadata,
        bool $has_approval_table,
        bool $is_approval_table_enabled,
        ?DocmanItemPermissionsForGroupsRepresentation $permissions_for_groups,
        ?int $parent_id
    ) {
        $this->id                         = $id;
        $this->title                      = $title;
        $this->description                = $description;
        $this->post_processed_description = $post_processed_description;
        $this->owner                      = $owner;
        $this->last_update_date           = $last_update_date;
        $this->creation_date              = $creation_date;
        $this->user_can_write             = $user_can_write;
        $this->can_user_manage            = $can_user_manage;
        $this->type                       = $type;
        $this->file_properties            = $file_properties;
        $this->embedded_file_properties   = $embedded_file_properties;
        $this->link_properties            = $link_properties;
        $this->wiki_properties            = $wiki_properties;
        $this->folder_properties          = $folder_properties;
        $this->is_expanded                = $is_expanded;
        $this->approval_table             = $approval_table;
        $this->lock_info                  = $lock_info;
        $this->metadata                   = $metadata;
        $this->has_approval_table         = $has_approval_table;
        $this->is_approval_table_enabled  = $is_approval_table_enabled;
        $this->permissions_for_groups     = $permissions_for_groups;
        $this->parent_id                  = $parent_id ?: 0;
    }

    /**
     * @param ItemMetadataRepresentation[] $metadata_representations
     */
    public static function build(
        \Docman_Item $item,
        Codendi_HTMLPurifier $purifier,
        MinimalUserRepresentation $owner,
        bool $user_can_write,
        ?string $type,
        bool $is_expanded,
        bool $can_user_manage,
        array $metadata_representations,
        bool $has_approval_table,
        bool $is_approval_table_enabled,
        ?ItemApprovalTableRepresentation $approval_table,
        ?ItemLockInfoRepresentation $lock_info,
        ?DocmanItemPermissionsForGroupsRepresentation $permissions_for_groups,
        ?FilePropertiesRepresentation $file_properties,
        ?IEmbeddedFilePropertiesRepresentation $embedded_file_properties,
        ?LinkPropertiesRepresentation $link_properties,
        ?WikiPropertiesRepresentation $wiki_properties,
        ?FolderPropertiesRepresentation $folder_properties
    ): self {
        $description = $item->getDescription();
        return new self(
            JsonCast::toInt($item->getId()),
            $item->getTitle(),
            $description,
            $purifier->purifyTextWithReferences($description, $item->getGroupId()),
            $owner,
            JsonCast::toDate($item->getUpdateDate()),
            JsonCast::toDate($item->getCreateDate()),
            $user_can_write,
            $can_user_manage,
            $type,
            $file_properties,
            $embedded_file_properties,
            $link_properties,
            $wiki_properties,
            $folder_properties,
            $is_expanded,
            $approval_table,
            $lock_info,
            $metadata_representations,
            $has_approval_table,
            $is_approval_table_enabled,
            $permissions_for_groups,
            JsonCast::toInt($item->getParentId())
        );
    }
}
