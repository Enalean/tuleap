<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Test\rest\Helper;

use ProjectUGroup;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;

class DocmanEmbeddedDataBuild
{
    /**
     * @var int
     */
    private $admin_user_id;
    /**
     * @var int
     */
    private $docman_user_id;

    /**
     * @var DocmanDataBuildCommon
     */
    private $common_builder;

    public function __construct(DocmanDataBuildCommon $common_builder)
    {
        $this->common_builder = $common_builder;
        $this->docman_user_id = $this->common_builder->getUserByName(DocmanDataBuildCommon::DOCMAN_REGULAR_USER_NAME);
        $this->admin_user_id  = $this->common_builder->getUserByName(DocmanDataBuilder::ADMIN_USER_NAME);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                 Embedded
     *                                   +
     *                                   |
     *                                   +
     *                         +-----------------+---------------+----------------------+
     *                         |                 |               |                      |
     *                         +                 +               +                      +
     *                 DELETE Embedded    LOCK Embedded  POST Embedded Version   PUT HM Embedded
     *
     * HM => Hardcoded Metadata
     *
     */
    public function createEmbeddedFileWithContent($docman_root)
    {
        $folder_embedded_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $docman_root->getId(),
            'Embedded',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_embedded_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createDeleteFolder($folder_embedded_id);
        $this->createLockFolder($folder_embedded_id);
        $this->createPostVersionFolder($folder_embedded_id);
        $this->createPutFolder($folder_embedded_id);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        DELETE Embedded
     *                           +
     *                           |
     *                           +
     *                  +----------------+---------------+
     *                  |                |               |
     *                  +                +               +
     *              DELETE E       DELETE E L       DELETE E RO
     *
     */
    private function createDeleteFolder(int $folder_id): void
    {
        $folder_delete_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'DELETE Embedded',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_delete_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_delete_id,
            'DELETE E',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createAndLockItem($folder_delete_id, $this->admin_user_id, $this->admin_user_id, 'DELETE E L', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);

        $this->common_builder->createAdminOnlyItem($folder_delete_id, 'DELETE E RO', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        LOCK Embedded
     *                           +
     *                           |
     *                           +
     *                  +----------------+---------------+-------------+
     *                  |                |               |             |
     *                  +                +               +             +
     *              LOCK E RO         LOCK E       LOCK E AL       LOCK E RF
     *
     * (RL)   => Docman Regular user Lock on this item
     * (AL)   => Docman Admin Lock on this item
     * (RO)   => Only admins has read permission this item
     */
    private function createLockFolder(int $folder_id): void
    {
        $folder_lock_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'LOCK Embedded',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_lock_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createAdminOnlyItem($folder_lock_id, 'LOCK E RO', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_lock_id,
            'LOCK E',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->docman_user_id, 'LOCK E RL', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);

        $this->common_builder->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->admin_user_id, 'LOCK E AL', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                     POST Embedded version
     *                           +
     *                           |
     *           +---------------+-----------------+-----------------+-----------------+-----------------+
     *           |               |                 |                 |                 |                 |
     *           +               +                 +                 +                 +                 +
     *       POST E V      POST E V AT C   POST E V AT R    POST E V AT E        POST E V L Admin  POST E V L
     *
     * F    => File
     * V    => version
     * AT C => Approval Table Copy action on new version
     * AT E => Approval Table Empty action on new version
     * AT R => Approval Table Reset action on new version
     * No AT => No approval table
     * L    => The item is locked by a regular user
     * L admin => THe item is locked by an admin
     */
    private function createPostVersionFolder(int $folder_id): void
    {
        $folder_post_version_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'POST Embedded version',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->common_builder->addWritePermissionOnItem($folder_post_version_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_post_version_id,
            'POST E V',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createItemWithApprovalTable(
            $folder_post_version_id,
            'POST E V AT C',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            $this->docman_user_id,
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createItemWithApprovalTable(
            $folder_post_version_id,
            'POST E V AT E',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            $this->docman_user_id,
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createItemWithApprovalTable(
            $folder_post_version_id,
            'POST E V AT R',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            $this->docman_user_id,
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_post_version_id,
            'POST E V No AT',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->admin_user_id,
            'POST E V L Admin',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $this->common_builder->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->docman_user_id,
            'POST E V L',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
        $this->common_builder->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->docman_user_id,
            'POST E V UL Admin',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        PUT HM Embedded
     *                           +
     *                           |
     *                           +
     *                        PUT E
     *
     * F OD => The file will be updated with a new obsolescence date metadata
     * F Status => The File will be updated with a new status metadata
     * F O => The file will be updated with a new unexcisting owner
     */
    private function createPutFolder(int $folder_id): void
    {
        $folder_put_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'PUT HM Embedded',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_put_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_put_id,
            'PUT E',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
    }
}
