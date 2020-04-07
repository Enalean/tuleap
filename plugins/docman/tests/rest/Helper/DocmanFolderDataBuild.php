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

class DocmanFolderDataBuild
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
     *                Folder
     *                  +
     *                  |
     *                  +
     *      +-----------+----------------+
     *      |           |                |
     *      +           +                +
     *   GET FO      GET FO RO       DELETE FO
     *
     *
     */
    public function createFolderWithContent($docman_root): void
    {
        $folder_file_id       = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $docman_root->getId(),
            'Folder',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_file_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createGetFolder($folder_file_id);
        $this->createGetROFolder($folder_file_id);
        $this->createDeleteFolder($folder_file_id);
    }
    /**
     * To help understand tests structure, below a representation of folder 1 hierarchy
     *
     *                      GET FO
     *                         +
     *                         |
     *     +---------+---------+----------+---------+-------------+
     *     +         +         +          +         +             +
     *    GET EM   GET F    GET L      GET L      GET W        GET E
     *
     */
    private function createGetFolder(int $get_folder_id): void
    {
        $folder_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $get_folder_id,
            'GET FO',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_id, ProjectUGroup::PROJECT_MEMBERS);
        $this->common_builder->addWritePermissionOnItem($folder_id, ProjectUGroup::PROJECT_ADMIN);

        $empty_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'GET EM',
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $file_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'GET F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $link_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'GET L',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            "https://example.test"
        );

        $embedded_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'GET E',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $wiki_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'GET W',
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            '',
            'MyWikiPage'
        );

        $this->common_builder->appendCustomMetadataValueToItem($empty_id, "custom value");
        $this->common_builder->appendCustomMetadataValueToItem($file_id, "custom value");
        $this->common_builder->appendCustomMetadataValueToItem($link_id, "custom value");
        $this->common_builder->appendCustomMetadataValueToItem($embedded_id, "custom value");
        $this->common_builder->appendCustomMetadataValueToItem($wiki_id, "custom value");
    }

    private function createGetROFolder(int $folder_file_id): void
    {
        $folder_id = $this->common_builder->createItemWithVersion(
            $this->admin_user_id,
            $folder_file_id,
            'GET FO RO',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addReadPermissionOnItem($folder_id, \ProjectUGroup::PROJECT_ADMIN);

        $this->common_builder->createItemWithVersion(
            $this->admin_user_id,
            $folder_id,
            'E RO',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
    }

    private function createDeleteFolder(int $folder_file_id): void
    {
        $folder_delete_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_file_id,
            'DELETE Folder',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_delete_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_delete_id,
            'DELETE FO',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->common_builder->createAndLockItem($folder_delete_id, $this->admin_user_id, $this->admin_user_id, 'DELETE FO L', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);

        $this->common_builder->createAdminOnlyItem($folder_delete_id, 'DELETE FO RO', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
    }
}
