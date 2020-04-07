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

class DocmanFileDataBuild
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
     *                                 File
     *                                   +
     *                                   |
     *                                   +
     *                  +----------------+---------------+--------------------+
     *                  |                |               |                    |
     *                  +                +               +                    +
     *             PUT HM File       DELETE File  POST File Version   LOCK File
     *
     * HM => Hardcoded Metadata
     *
     */
    public function createFolderFileWithContent($docman_root): void
    {
        $folder_file_id       = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $docman_root->getId(),
            'File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_file_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createPutFolder($folder_file_id);
        $this->createDeleteFolder($folder_file_id);
        $this->createPostVersionFolder($folder_file_id);
        $this->createLockFolder($folder_file_id);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        PUT HM File
     *                           +
     *                           |
     *                           +
     *                  +--------+-------+-------------+
     *                  |                |             |
     *                  +                +             +
     *             PUT F OD         PUT F Status     PUT F
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
            'PUT HM File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_put_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_put_id,
            'PUT F OD',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_put_id,
            'PUT F Status',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_put_id,
            'PUT F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        DELETE File
     *                           +
     *                           |
     *                           +
     *                  +----------------+---------------+
     *                  |                |               |
     *                  +                +               +
     *              DELETE F       DELETE F L       DELETE F RO
     *
     */
    private function createDeleteFolder(int $folder_id): void
    {
        $folder_delete_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'DELETE File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_delete_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_delete_id,
            'DELETE F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createAndLockItem($folder_delete_id, $this->admin_user_id, $this->admin_user_id, 'DELETE F L', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $this->common_builder->createAdminOnlyItem($folder_delete_id, 'DELETE F RO', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                     POST File version
     *                           +
     *                           |
     *           +---------------+-----------------+-----------------+-----------------+-----------------+
     *           |               |                 |                 |                 |                 |
     *           +               +                 +                 +                 +                 +
     *       POST F V      POST F V AT C   POST F V AT R    POST F V AT E        POST F V L Admin  POST F V L
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
            'POST File Version',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->common_builder->addWritePermissionOnItem($folder_post_version_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_post_version_id,
            'POST F V',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithApprovalTable(
            $folder_post_version_id,
            'POST F V AT C',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            $this->docman_user_id,
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithApprovalTable(
            $folder_post_version_id,
            'POST F V AT E',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            $this->docman_user_id,
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithApprovalTable(
            $folder_post_version_id,
            'POST F V AT R',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            $this->docman_user_id,
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_post_version_id,
            'POST F V No AT',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->admin_user_id,
            'POST F V L Admin',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->docman_user_id,
            'POST F V L',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $this->common_builder->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->docman_user_id,
            'POST F V UL Admin',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        LOCK File
     *                           +
     *                           |
     *                           +
     *                  +----------------+---------------+-------------+
     *                  |                |               |             |
     *                  +                +               +             +
     *              LOCK F RO         LOCK F       LOCK F AL       LOCK F RF
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
            'LOCK File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_lock_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createAdminOnlyItem($folder_lock_id, 'LOCK F RO', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_lock_id,
            'LOCK F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->docman_user_id, 'LOCK F RL', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $this->common_builder->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->admin_user_id, 'LOCK F AL', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
    }
}
