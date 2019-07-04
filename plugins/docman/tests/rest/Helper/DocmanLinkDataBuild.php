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

namespace Tuleap\Docman\Test\rest\Helper;

use Docman_ItemFactory;
use ProjectUGroup;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;

class DocmanLinkDataBuild
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
     *                                 Link
     *                                   +
     *                                   |
     *                                   +
     *      +------------------+---------+
     *      |                  |         |
     *      +                  +         +
     * PATCH Link    DELETE Link    LOCK Link
     *
     * HM => Hardcoded Metadata
     *
     */
    public function createLinkFileWithContent($docman_root): void
    {
        $folder_link_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $docman_root->getId(),
            'Link',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_link_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createPatchFolder($folder_link_id);
        $this->createDeleteFolder($folder_link_id);
        $this->createLockFolder($folder_link_id);
    }


    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                       PATCH Link
     *                                            +
     *                                            |
     *                                            +
     *    +-------------+-------------+-----------------+--------------+----------+-------------+-----------+---------+
     *    |             |             |                 |              |          |             |           |         |
     *    +             +             +                 +              +          +             +           +         +
     *  PATCH L AT C  PATCH L AT R  PATCH L AT E   PATCH L AT   PATCH L NO AT  PATCH L AL   PATCH L KO     PATCH L
     *  PATCH L RL
     *
     *
     * (RL)   => Docman Regular user Lock on this item
     * (AL)   => Docman Admin Lock on this item
     * (AT)   => Approval table on this item
     * (AT C) => Copy Approval table on this item
     * (AT R) => Reset Approval table on this item
     * (AT E) => Empty Approval table on this item
     * (DIS AT) => Disabled Approval table on this item
     */
    private function createPatchFolder(int $folder_id): void
    {
        $folder_link_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'PATCH Link',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_link_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createLinkWithApprovalTable($folder_link_id, 'PATCH L AT C', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED, $this->docman_user_id, PLUGIN_DOCMAN_ITEM_TYPE_LINK, 9000);
        $this->createLinkWithApprovalTable($folder_link_id, 'PATCH L AT R', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED, $this->docman_user_id, PLUGIN_DOCMAN_ITEM_TYPE_LINK, 9001);
        $this->createLinkWithApprovalTable($folder_link_id, 'PATCH L AT E', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED, $this->docman_user_id, PLUGIN_DOCMAN_ITEM_TYPE_LINK, 9002);
        $this->createLinkWithApprovalTable($folder_link_id, 'PATCH L AT', PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED, $this->docman_user_id, PLUGIN_DOCMAN_ITEM_TYPE_LINK, 9003);

        $this->common_builder->createItem(
            $this->docman_user_id,
            $folder_link_id,
            'PATCH L NO AT',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );
        $this->common_builder->createItem(
            $this->docman_user_id,
            $folder_link_id,
            'PATCH L KO',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );
        $this->common_builder->createItem(
            $this->docman_user_id,
            $folder_link_id,
            'PATCH L',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->common_builder->createAndLockItem(
            $folder_link_id,
            $this->docman_user_id,
            $this->admin_user_id,
            'PATCH L AL',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->common_builder->createAndLockItem(
            $folder_link_id,
            $this->docman_user_id,
            $this->docman_user_id,
            'PATCH L RL',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        DELETE Link
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
            'DELETE Link',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_delete_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_delete_id,
            'DELETE L',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->common_builder->createAndLockItem($folder_delete_id, $this->admin_user_id, $this->admin_user_id, 'DELETE L L', PLUGIN_DOCMAN_ITEM_TYPE_LINK);

        $this->common_builder->createAdminOnlyItem($folder_delete_id, 'DELETE L RO', PLUGIN_DOCMAN_ITEM_TYPE_LINK);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        LOCK Link
     *                           +
     *                           |
     *                           +
     *                  +----------------+---------------+-------------+
     *                  |                |               |             |
     *                  +                +               +             +
     *              LOCK L RO         LOCK L       LOCK L AL       LOCK L RF
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
            'LOCK Link',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_lock_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createAdminOnlyItem($folder_lock_id, 'LOCK L RO', PLUGIN_DOCMAN_ITEM_TYPE_LINK);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_lock_id,
            'LOCK L',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->common_builder->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->docman_user_id, 'LOCK L RL', PLUGIN_DOCMAN_ITEM_TYPE_LINK);

        $this->common_builder->createAndLockItem($folder_lock_id, $this->admin_user_id, $this->admin_user_id, 'LOCK L AL', PLUGIN_DOCMAN_ITEM_TYPE_LINK);
    }

    private function addLinkWithCustomVersionNumber(int $item_id, int $version): int
    {
        $docman_factory = new Docman_ItemFactory();
        $docman_link    = $docman_factory->getItemFromDb($item_id);
        /**
         * @var \Docman_Link $docman_link
         */
        $docman_link->setUrl('https://my.example.test');
        $version_link_factory = new \Docman_LinkVersionFactory();
        $version_link_factory->createLinkWithSpecificVersion(
            $docman_link,
            'changset1',
            'test rest Change',
            time(),
            $version
        );
        $link_version = $version_link_factory->getLatestVersion($docman_link);
        return (int)$link_version->getId();
    }

    public function createLinkWithApprovalTable(
        int $folder_id,
        string $title,
        int $approval_status,
        int $user_id,
        int $item_type,
        int $version
    ): void {
        $item_id         = $this->common_builder->createItem(
            $user_id,
            $folder_id,
            $title,
            $item_type
        );

        $version_id = $this->addLinkWithCustomVersionNumber($item_id, $version);

        $this->common_builder->addApprovalTable($title, (int)$version_id, $approval_status);
    }
}
