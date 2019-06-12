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

declare(strict_types = 1);

namespace Tuleap\Docman\rest\v1;

use Tuleap\Docman\rest\DocmanDataBuilder;

class DocmanFileDataBuild extends DocmanDataBuildCommon
{

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
     *              PATCH File       DELETE File     POST File Version    LOCK File
     *
     */
    public function createFolderFileWithContent($docman_root): void
    {
        $this->docman_user_id = $this->getUseByName(self::DOCMAN_REGULAR_USER_NAME);
        $this->admin_user_id = $this->getUseByName(DocmanDataBuilder::ADMIN_USER_NAME);
        $folder_file_id       = $this->createItemWithVersion(
            $this->docman_user_id,
            $docman_root->getId(),
            'File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->addWritePermissionOnItem($folder_file_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->createPatchFolder($folder_file_id);
        $this->createDeleteFolder($folder_file_id);
        $this->createPostVersionFolder($folder_file_id);
        $this->createLockFolder($folder_file_id);
    }


    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                       PATCH File
     *                                            +
     *                                            |
     *                                            +
     *    +-------------+-------------+-----------------+--------------+----------+-------------+-----------+---------+
     *    |             |             |                 |              |          |             |           |         |
     *    +             +             +                 +              +          +             +           +         +
     *  PATCH F AT C  PATCH F AT R  PATCH F AT E   PATCH F AT   PATCH F NO AT  PATCH F AL   PATCH F KO     PATCH F   PATCH F RL
     *
     *
     * (RL)   => Docman Regular user Lock on this item
     * (AL)   => Docman Admin Lock on this item
     * (AT)   => Approval table on this item
     * (AT C) => Copy Approval table on this item
     * (AT R) => Reset Approval table on this item
     * (AT E) => Empty Approval table on this item
     * (DIS AT) => Disabled Approval table on this item
     *
     *
     * @param $docman_root
     */
    private function createPatchFolder(int $folder_id): void
    {
        $folder_file_id = $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'PATCH File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->addWritePermissionOnItem($folder_file_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->createFileWithApprovalTable($folder_file_id, 'PATCH F AT C', 'version title', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->createFileWithApprovalTable($folder_file_id, 'PATCH F AT R', 'version title', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->createFileWithApprovalTable($folder_file_id, 'PATCH F AT E', 'version title', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->createFileWithApprovalTable($folder_file_id, 'PATCH F AT', 'version title', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);

        $this->createItem(
            $this->docman_user_id,
            $folder_file_id,
            'PATCH F NO AT',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $this->createItem(
            $this->docman_user_id,
            $folder_file_id,
            'PATCH F KO',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $this->createItem(
            $this->docman_user_id,
            $folder_file_id,
            'PATCH F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createAndLockItem(
            $folder_file_id,
            $this->docman_user_id,
            $this->admin_user_id,
            'PATCH F AL',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createAndLockItem(
            $folder_file_id,
            $this->docman_user_id,
            $this->docman_user_id,
            'PATCH F RL',
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
        $folder_delete_id = $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'DELETE File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->addWritePermissionOnItem($folder_delete_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_delete_id,
            'DELETE F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createAndLockItem($folder_delete_id, $this->admin_user_id, $this->admin_user_id, 'DELETE F L', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $this->createAdminOnlyItem($folder_delete_id, 'DELETE F RO', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
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
        $folder_post_version_id = $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'POST File Version',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->addWritePermissionOnItem($folder_post_version_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_post_version_id,
            'POST F V',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createFileWithApprovalTable(
            $folder_post_version_id,
            'POST F V AT C',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED
        );

        $this->createFileWithApprovalTable(
            $folder_post_version_id,
            'POST F V AT E',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED
        );

        $this->createFileWithApprovalTable(
            $folder_post_version_id,
            'POST F V AT R',
            'version title',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED
        );

        $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_post_version_id,
            'POST F V No AT',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->admin_user_id,
            'POST F V L Admin',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createAndLockItem(
            $folder_post_version_id,
            $this->admin_user_id,
            $this->docman_user_id,
            'POST F V L',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $this->createAndLockItem(
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
        $folder_lock_id = $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'LOCK File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->addWritePermissionOnItem($folder_lock_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->createAdminOnlyItem($folder_lock_id, 'LOCK F RO', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $this->createItemWithVersion(
            $this->docman_user_id,
            $folder_lock_id,
            'LOCK F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->docman_user_id, 'LOCK F RL', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $this->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->admin_user_id, 'LOCK F AL', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
    }
}
