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
     *    +-----+-----+------------------+--------------+------------+-------+
     *    |           |                  |              |            |       |
     *    +           +                  +              +            +       +
     * folder 1   Folder C wiki   Folder D Link   Trash (Folder)    File   Embedded
     *    +          +                     +
     *    |          |                     |
     *   ...        ...                   ...
     *
     * * HM => Hardcoded Metadata
     */
    private function addContent()
    {
        $docman_root = $this->docman_item_factory->getRoot($this->project->getID());

        $file_builder = new DocmanFileDataBuild(new DocmanDataBuildCommon(self::PROJECT_NAME));
        $file_builder->createFolderFileWithContent($docman_root);

        $file_builder = new DocmanEmbeddedDataBuild(new DocmanDataBuildCommon(self::PROJECT_NAME));
        $file_builder->createEmbeddedFileWithContent($docman_root);

        $this->createFolderLinkWithContent($docman_root);
        $this->createFolder1WithSubContent($docman_root);
        $this->createFolderWikiWithContent($docman_root);
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
        $file_DIS_AT_version_id = $this->addFileVersion($file_DIS_AT_id, ':o !', 'application/pdf', "");
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

        $item_F_path = dirname(__FILE__) . '/_fixtures/docmanFile/embeddedFile';
        $embedded_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'embeddedFile',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            '',
            $item_F_path
        );

        $wiki_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_id,
            'wiki',
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            '',
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
     *                     Folder C Wiki
     *                          +
     *                          |
     *                          +
     *             +------------------------+-------------------+
     *             |                        |                   |
     *             +                        +                   +
     *          wiki AT                   wiki L           wiki POST L
     *
     * (L)    => Lock on this item
     * (AT)   => Approval table on this item
     *
     */
    private function createFolderWikiWithContent($docman_root): void
    {
        $folder_wiki_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Folder C Wiki',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $wiki_ATC_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_wiki_id,
            'wiki AT',
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );

        $this->addWritePermissionOnItem($wiki_ATC_id, ProjectUGroup::PROJECT_MEMBERS);

        $wiki_L_id = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_wiki_id,
            'wiki L',
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );

        $this->createItem(
            self::REGULAR_USER_ID,
            $folder_wiki_id,
            'wiki POST L',
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );

        $this->addApprovalTableForWiki((int)$wiki_ATC_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addReadPermissionOnItem($folder_wiki_id, \ProjectUGroup::PROJECT_ADMIN);

        $this->lockItem($wiki_L_id, self::REGULAR_USER_ID);

        $this->appendCustomMetadataValueToItem($folder_wiki_id, "custom value for folder_3");
        $this->appendCustomMetadataValueToItem($wiki_ATC_id, "custom value for wiki AT");
    }

    private function addApprovalTableForWiki(int $item_id, int $status): void
    {
        $dao = new Docman_ApprovalTableWikiDao();
        $table_id = $dao->createTable(
            $item_id,
            0,
            self::REGULAR_USER_ID,
            "",
            time(),
            $status,
            false
        );

        $reviewer_dao = new \Docman_ApprovalTableReviewerDao(\CodendiDataAccess::instance());
        $reviewer_dao-> addUser($table_id, self::REGULAR_USER_ID);
        $reviewer_dao->updateReview($table_id, self::REGULAR_USER_ID, time(), 1, "", 1);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                      Folder D link
     *                                            +
     *                                            |
     *                                            +
     *                  +------------+------------+--------------+-----------+----------+-------------+
     *                  |            |            |              |           |          |             |
     *                  +            +            +              +           +          +             +
     *          link AT C        link AT R    link AT E   link DIS AT    link NO AT   link L       link POST L
     *
     * (L)    => Lock on this item
     * (AT)   => Approval table on this item
     * (AT C) => Copy Approval table on this item
     * (AT R) => Reset Approval table on this item
     * (AT E) => Empty Approval table on this item
     * (DIS AT) => Disabled Approval table on this item
     *
     */
    private function createFolderLinkWithContent($docman_root)
    {
        $folder_link_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Folder D Link',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $link_ATC_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link AT C',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );
        $link_ATC_version_id = 500;
        $this->addLinkWithCustomVersionNumber($link_ATC_id, $link_ATC_version_id);

        $this->addWritePermissionOnItem($link_ATC_id, ProjectUGroup::PROJECT_MEMBERS);
        $link_ATR_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link AT R',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );
        $link_ATR_version_id = 501;
        $this->addLinkWithCustomVersionNumber($link_ATR_id, $link_ATR_version_id);
        $this->addWritePermissionOnItem($link_ATR_id, ProjectUGroup::PROJECT_MEMBERS);

        $link_ATE_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link AT E',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $link_ATE_version_id = 502;
        $this->addLinkWithCustomVersionNumber($link_ATE_id, $link_ATE_version_id);
        $this->addWritePermissionOnItem($link_ATE_id, ProjectUGroup::PROJECT_MEMBERS);

        $link_DIS_AT_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link DIS AT',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );
        $link_DIS_AT_version_id = 503;
        $this->addLinkWithCustomVersionNumber($link_DIS_AT_id, $link_DIS_AT_version_id);
        $this->addWritePermissionOnItem($link_DIS_AT_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addApprovalTable("link_DIS_AT", (int)$link_DIS_AT_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);

        $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link NO AT',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $link_L_id = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link L',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->createItem(
            self::REGULAR_USER_ID,
            $folder_link_id,
            'link POST L',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->addApprovalTable("link_ATC", (int)$link_ATC_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addApprovalTable("link_ATE", (int)$link_ATE_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addApprovalTable("link_ATR", (int)$link_ATR_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);

        $this->lockItem($link_L_id, self::REGULAR_USER_ID);

        $this->appendCustomMetadataValueToItem($folder_link_id, "custom value for folder_3");

        $this->appendCustomMetadataValueToItem($link_ATC_id, "custom value for link ATC");
        $this->appendCustomMetadataValueToItem($link_ATR_id, "custom value for link ATR");
        $this->appendCustomMetadataValueToItem($link_ATE_id, "custom value for link ATE");
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

        $link_L_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "old link L",
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $this->addReadPermissionOnItem($link_L_id, ProjectUGroup::DOCUMENT_ADMIN);

        $wiki_L_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "old wiki L",
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );

        $this->addReadPermissionOnItem($wiki_L_id, ProjectUGroup::DOCUMENT_ADMIN);

        $folder_L_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "old folder L",
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $folder_with_content_you_cannot_delete = $this->createItem(
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
            $link_L_id,
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            time()
        );

        $dao->addLock(
            $wiki_L_id,
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            time()
        );

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
            "another old link",
            PLUGIN_DOCMAN_ITEM_TYPE_LINK
        );

        $another_wiki_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "another old wiki",
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );

        $this->addReadPermissionOnItem($another_wiki_id, self::REGULAR_USER_ID);

        $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "another old folder",
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $wiki_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_with_content_you_cannot_delete,
            "wiki",
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );

        $this->addReadPermissionOnItem($wiki_id, self::REGULAR_USER_ID);

        $another_empty_doc_id = $this->createItem(
            \REST_TestDataBuilder::ADMIN_PROJECT_ID,
            $folder_delete_id,
            "another old empty doc",
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $this->addReadPermissionOnItem($another_empty_doc_id, self::REGULAR_USER_ID);
    }
}
