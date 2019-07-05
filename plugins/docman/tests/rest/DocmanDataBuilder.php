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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest;

use Docman_ApprovalTableWikiDao;
use ProjectUGroup;
use Tuleap\Docman\Test\rest\Helper\DocmanDataBuildCommon;
use Tuleap\Docman\Test\rest\Helper\DocmanEmbeddedDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanFileDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanLinkDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanWikiDataBuild;

class DocmanDataBuilder extends DocmanDataBuildCommon
{
    public const PROJECT_NAME = 'DocmanProject';

    private const ANON_ID = 0;

    public function setUp(): void
    {
        echo 'Setup Docman REST Tests configuration' . PHP_EOL;

        $this->installPlugin($this->project);
        $this->generateDocmanRegularUser();
        $this->createCustomMetadata();
        $this->addContent();
    }


    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *         Root
     *          +
     *          |
     *    +-----+-----+--------------+--------+-------+---------+
     *    |           |              |        |       |         |
     *    +           +              +        +       +         +
     * folder 1   Trash (Folder)   File   Embedded   Link      Wiki
     *    +           +              +        +       +         +
     *    |           |              |        |       |         |
     *   ...         ...            ...      ...     ...       ...
     *
     * * HM => Hardcoded Metadata
     */
    private function addContent()
    {
        $docman_root = $this->docman_item_factory->getRoot($this->project->getID());

        $common_builder = new DocmanDataBuildCommon(self::PROJECT_NAME);

        $file_builder   = new DocmanFileDataBuild($common_builder);
        $file_builder->createFolderFileWithContent($docman_root);

        $file_builder = new DocmanEmbeddedDataBuild($common_builder);
        $file_builder->createEmbeddedFileWithContent($docman_root);

        $link_builder = new DocmanLinkDataBuild($common_builder);
        $link_builder->createLinkFileWithContent($docman_root);

        $wiki_builder = new DocmanWikiDataBuild($common_builder);
        $wiki_builder->createWikiWithContent($docman_root);

        $this->createFolder1WithSubContent($docman_root);
        $this->createFolderContentToDelete($docman_root);
    }

     /**
     * To help understand tests structure, below a representation of folder 1 hierarchy
     *
     *                      folder 1
     *                         +
     *                         |
     *     +---------+---------+----------+---------+-------------+
     *     +         +         +          +         +             +
     *    empty    file      folder      link  embeddedFile     wiki
     *                         +
     *                         |
     *                         +
     *                    embeddedFile 2
     *
     */
    private function createFolder1WithSubContent($docman_root)
    {
        $folder_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'folder 1',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $ro_folder_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Folder RO',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->addReadPermissionOnItem($ro_folder_id, \ProjectUGroup::PROJECT_ADMIN);

        $empty_id = $this->createItemWithVersion(
            self::ANON_ID,
            $folder_id,
            'empty',
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $file_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'file',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $file_DIS_AT_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_id,
            'file DIS AT',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $file_path              = __DIR__ . '/_fixtures/docmanFile/file.txt';
        $file_DIS_AT_version_id = $this->addFileVersion($file_DIS_AT_id, ':o !', 'application/pdf', $file_path);
        $this->addWritePermissionOnItem($file_DIS_AT_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addApprovalTable("file_DIS_AT", (int)$file_DIS_AT_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);

        $folder_2_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'folder',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $embedded_2_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_2_id,
            'embeddedFile 2',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $link_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'link',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            "https://example.test"
        );


        $embedded_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'embeddedFile',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $wiki_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'wiki',
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            '',
            'MyWikiPage'
        );

        $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'Empty POST L',
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $this->addWritePermissionOnItem($folder_id, ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($empty_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($file_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($folder_2_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($embedded_2_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($link_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($embedded_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($wiki_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->appendCustomMetadataValueToItem($empty_id, "custom value for item_A");
        $this->appendCustomMetadataValueToItem($file_id, "custom value for item_C");
        $this->appendCustomMetadataValueToItem($folder_2_id, "custom value for folder_2");
        $this->appendCustomMetadataValueToItem($embedded_2_id, "custom value for item_D");
        $this->appendCustomMetadataValueToItem($link_id, "custom value for item_E");
        $this->appendCustomMetadataValueToItem($embedded_id, "custom value for item_F");
        $this->appendCustomMetadataValueToItem($wiki_id, "custom value for item_G");
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                    Trash
     *                                      +
     *                                      |
     *                                      +
     *             +----------------+-------|-------+-------------+-----------------+
     *             |                |               |             |                 |
     *             +                +               +             +                 +
     *         old file L    another old file   old link L   another old link   and so on ...
     *
     * (L)    => Lock on this item
     *
     */
    private function createFolderContentToDelete(\Docman_Item $docman_root): void
    {
        $folder_delete_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Trash',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $folder_L_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "old folder L",
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "folder with content you cannot delete",
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $empty_doc_L_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "old empty doc L",
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $dao = new \Docman_LockDao();

        $dao->addLock(
            $folder_L_id,
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            time()
        );

        $dao->addLock(
            $empty_doc_L_id,
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            time()
        );

        $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "another old folder",
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $another_empty_doc_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "another old empty doc",
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $this->addReadPermissionOnItem($another_empty_doc_id, self::REGULAR_USER_ID);
    }
}
