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
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

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
     * @var string {@type string}
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
     * @var string {@type string}
     */
    public $last_update_date;

    /**
     * @var string {@type string}
     */
    public $creation_date;

    /**
     * @var bool {@type bool}
     */
    public $user_can_write;

    /**
     * @var string
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

    public function build(
        \Docman_Item $item,
        Codendi_HTMLPurifier $purifier,
        MinimalUserRepresentation $owner,
        $user_can_write,
        $type,
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
        ?WikiPropertiesRepresentation $wiki_properties
    ) {
        $this->id                         = JsonCast::toInt($item->getId());
        $this->title                      = $item->getTitle();
        $this->description                = $item->getDescription();
        $this->post_processed_description = $purifier->purifyTextWithReferences($this->description, $item->getGroupId());
        $this->owner                      = $owner;
        $this->last_update_date           = JsonCast::toDate($item->getUpdateDate());
        $this->creation_date              = JsonCast::toDate($item->getCreateDate());
        $this->user_can_write             = $user_can_write;
        $this->can_user_manage            = $can_user_manage;
        $this->type                       = $type;
        $this->file_properties            = $file_properties;
        $this->embedded_file_properties   = $embedded_file_properties;
        $this->link_properties            = $link_properties;
        $this->wiki_properties            = $wiki_properties;
        $this->is_expanded                = $is_expanded;
        $this->approval_table             = $approval_table;
        $this->lock_info                  = $lock_info;
        $this->metadata                   = $metadata_representations;
        $this->has_approval_table         = $has_approval_table;
        $this->is_approval_table_enabled  = $is_approval_table_enabled;
        $this->permissions_for_groups     = $permissions_for_groups;

        $parent_id = JsonCast::toInt($item->getParentId());

        $this->parent_id = ($parent_id) ? $parent_id : 0;
    }
}
