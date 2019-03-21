<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Docman\rest;

use Docman_ApprovalTableItemDao;
use Docman_ItemFactory;
use Docman_MetadataValueFactory;
use PluginManager;
use Project;
use ProjectUGroup;
use REST_TestDataBuilder;

require_once __DIR__ .'/DocmanDatabaseInitialization.php';

class DocmanDataBuilder extends REST_TestDataBuilder
{
    const         PROJECT_NAME                 = 'DocmanProject';
    const         DOCMAN_REGULAR_USER_NAME     = 'docman_regular_user';
    const         DOCMAN_REGULAR_USER_PASSWORD = 'welcome0';
    private const ANON_ID                      = 0;
    private const REGULAR_USER_ID              = 102;
    /**
     * @var \PFUser
     */
    private $docman_user;

    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;

    /**
     * @var \Docman_MetadataFactory
     */
    private $metadata_factory;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var Docman_MetadataValueFactory
     */
    private $metadata_value_factory;

    public function setUp()
    {
        echo 'Setup Docman REST Tests configuration' . PHP_EOL;

        $this->project = $this->project_manager->getProjectByUnixName(self::PROJECT_NAME);

        $this->docman_item_factory    = Docman_ItemFactory::instance($this->project->getID());
        $this->metadata_factory       = new \Docman_MetadataFactory($this->project->getID());
        $this->metadata_value_factory = new Docman_MetadataValueFactory($this->project->getID());

        $this->installPlugin();
        $this->createCustomMetadata();
        $this->addContent();
        $this->generateDocmanRegularUser();
    }

    private function installPlugin()
    {
        $plugin_manager = PluginManager::instance();
        $plugin_manager->installAndActivate('docman');
        $this->activateWikiServiceForTheProject();
    }

    private function createItemWithVersion(
        $user_id,
        $docman_root_id,
        $title,
        $item_type,
        $link_url = '',
        $file_path = '',
        $wiki_page = ''
    ) {
        $item_id = $this->createItem($user_id, $docman_root_id, $title, $item_type, $link_url, $wiki_page);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                $file_type = 'text/html';
                $this->addFileVersion($item_id, $title, $file_type, $file_path);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $file_type = 'application/pdf';
                $this->addFileVersion($item_id, $title, $file_type);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $this->addLinkVersion($item_id);
                break;
            default:
                $file_type = null;
                break;
        }
        return $item_id;
    }

    private function createItem($user_id, $docman_root_id, $title, $item_type, $link_url = '', $wiki_page = '')
    {
        $item = array(
            'parent_id'         => $docman_root_id,
            'group_id'          => $this->project->getID(),
            'title'             => $title,
            'description'       => '',
            'create_date'       => time(),
            'update_date'       => time(),
            'user_id'           => $user_id,
            'status'            => 100,
            'obsolescence_date' => 0,
            'rank'              => 1,
            'item_type'         => $item_type,
            'link_url'          => $link_url,
            'wiki_page'         => $wiki_page,
            'file_is_embedded'  => ''
        );

        return  $this->docman_item_factory->create($item, 1);
    }

    private function addFileVersion($item_id, $title, $item_type, $file_path = '')
    {
        $version         = array(
            'item_id'   => $item_id,
            'number'    => 1,
            'user_id'   => 102,
            'label'     => '',
            'changelog' => '',
            'date'      => time(),
            'filename'  => $title,
            'filesize'  => 3,
            'filetype'  => $item_type,
            'path'      => $file_path
        );
        $version_factory = new \Docman_VersionFactory();
        return $version_factory->create($version);
    }

    private function addLinkVersion(int $item_id)
    {
        $docman_factory = new Docman_ItemFactory();
        $docman_link    = $docman_factory->getItemFromDb($item_id);
        $docman_link->setUrl('https://my.example.test');
        $version_link_factory = new \Docman_LinkVersionFactory();
        $version_link_factory->create($docman_link, 'changset1', 'test rest Change', time());
        $link_version = $version_link_factory->getLatestVersion($docman_link);
        return $link_version->getId();
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *         Root
     *          +
     *          |
     *    +-----+-----+---------------+
     *    |           |               |
     *    +           +               +
     * folder 1   Folder A File   Folder B Embedded
     *    +           +               +
     *    |           |               |
     *   ...         ...             ...
     */
    private function addContent()
    {
        $docman_root = $this->docman_item_factory->getRoot($this->project->getID());

        $this->createFolder1WithSubContent($docman_root);
        $this->createFolderFileWithContent($docman_root);
        $this->createFolderEmbeddedWithContent($docman_root);
    }

    private function addReadPermissionOnItem($object_id, $ugroup_name)
    {
        permission_add_ugroup(
            $this->project->getID(),
            'PLUGIN_DOCMAN_READ',
            $object_id,
            $ugroup_name,
            true
        );
    }

    private function addWritePermissionOnItem($object_id, $ugroup_name)
    {
        permission_add_ugroup(
            $this->project->getID(),
            'PLUGIN_DOCMAN_WRITE',
            $object_id,
            $ugroup_name,
            true
        );
    }

    private function generateDocmanRegularUser()
    {
        $this->docman_user = $this->user_manager->getUserByUserName(self::DOCMAN_REGULAR_USER_NAME);
        $this->docman_user->setPassword(self::DOCMAN_REGULAR_USER_PASSWORD);
        $this->user_manager->updateDb($this->docman_user);
    }

    private function activateWikiServiceForTheProject(): void
    {
        $this->project = $this->project_manager->getProjectByUnixName(self::PROJECT_NAME);
        $initializer   = new DocmanDatabaseInitialization();
        $initializer->setup($this->project);
    }

    private function createCustomMetadata(): void
    {
        $custom_metadata = new \Docman_Metadata();

        $custom_metadata->setName("Custom metadata");
        $custom_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $custom_metadata->setDescription("A custom metadata used for testing purpose");
        $custom_metadata->setIsRequired(true);
        $custom_metadata->setIsEmptyAllowed(false);
        $custom_metadata->setIsMultipleValuesAllowed(false);
        $custom_metadata->setSpecial(false);
        $custom_metadata->setUseIt(true);

        $this->metadata_factory->create(
            $custom_metadata
        );
    }

    private function appendCustomMetadataValueToItem(int $item_id, string $value): void
    {
        $metadata_value = new \Docman_MetadataValueScalar();

        $metadata_value->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata_value->setItemId($item_id);
        $metadata_value->setFieldId(1);
        $metadata_value->setValueString($value);

        $this->metadata_value_factory->create($metadata_value);
    }

    private function addApprovalTableForFile(int $version_id, int $status): void
    {
        $dao = new Docman_ApprovalTableItemDao();
        $dao->createTable(
            'version_id',
            $version_id,
            self::REGULAR_USER_ID,
            "",
            time(),
            $status,
            false
        );
    }

    private function lockItem(int $item_id)
    {
        $dao = new \Docman_LockDao();
        $dao->addLock(
            $item_id,
            self::REGULAR_USER_ID,
            time()
        );
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                    Folder File (L) (AT)
     *                                            +
     *                                            |
     *                                            +
     *                  +----------------+--------+-------+----------------+---------------+
     *                  |                |                |                |               |
     *                  +                +                +                +               +
     *              file AT C       file AT R         file AT E        file DIS AT      file NO AT
     *
     * (L)    => Lock on this item
     * (AT)   => Approval table on this item
     * (AT C) => Copy Approval table on this item
     * (AT R) => Reset Approval table on this item
     * (AT E) => Empty Approval table on this item
     * (DIS AT) => Disabled Approval table on this item
     *
     */
    private function createFolderFileWithContent($docman_root)
    {
        $folder_3_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Folder A File',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $file_ATC_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_3_id,
            'file AT C',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $file_ATC_version_id = $this->addFileVersion($file_ATC_id, 'First !', 'application/pdf');

        $this->addWritePermissionOnItem($file_ATC_id, ProjectUGroup::PROJECT_MEMBERS);
        $file_ATR_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_3_id,
            'file AT R',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $file_ATR_version_id = $this->addFileVersion($file_ATR_id, 'Second !', 'application/pdf');
        $this->addWritePermissionOnItem($file_ATR_id, ProjectUGroup::PROJECT_MEMBERS);

        $file_ATE_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_3_id,
            'file AT E',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $file_ATE_version_id = $this->addFileVersion($file_ATE_id, 'Third !', 'application/pdf');
        $this->addWritePermissionOnItem($file_ATE_id, ProjectUGroup::PROJECT_MEMBERS);

        $file_DIS_AT_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_3_id,
            'file DIS AT',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
        $file_DIS_AT_version_id = $this->addFileVersion($file_DIS_AT_id, ':o !', 'application/pdf');
        $this->addWritePermissionOnItem($file_DIS_AT_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addApprovalTableForFile((int)$file_DIS_AT_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);

        $this->createItem(
            self::REGULAR_USER_ID,
            $folder_3_id,
            'file NO AT',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );


        $this->addApprovalTableForFile((int)$file_ATC_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addApprovalTableForFile((int)$file_ATR_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addApprovalTableForFile((int)$file_ATE_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addReadPermissionOnItem($folder_3_id, \ProjectUGroup::PROJECT_ADMIN);

        $this->lockItem($folder_3_id);

        $this->appendCustomMetadataValueToItem($folder_3_id, "custom value for folder_3");

        $this->appendCustomMetadataValueToItem($file_ATC_id, "custom value for file A");
        $this->appendCustomMetadataValueToItem($file_ATR_id, "custom value for file B");
        $this->appendCustomMetadataValueToItem($file_ATE_id, "custom value for file C");
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
     *                                    Folder Embedded
     *                                            +
     *                                            |
     *                                            +
     *                  +----------------+--------+-------+----------------+---------------+---------------+
     *                  |                |                |                |               |               |
     *                  +                +                +                +               +               +
     *          embedded AT C    embedded AT R     embedded AT E   embedded DIS AT  embedded NO AT     embedded L
     *
     * (L)    => Lock on this item
     * (AT)   => Approval table on this item
     * (AT C) => Copy Approval table on this item
     * (AT R) => Reset Approval table on this item
     * (AT E) => Empty Approval table on this item
     * (DIS AT) => Disabled Approval table on this item
     *
     */
    private function createFolderEmbeddedWithContent($docman_root)
    {
        $folder_embedded_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Folder B Embedded',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $embedded_ATC_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_embedded_id,
            'embedded AT C',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
        $embedded_ATC_version_id = $this->addEmbeddedVersion($embedded_ATC_id, 'First !');

        $this->addWritePermissionOnItem($embedded_ATC_id, ProjectUGroup::PROJECT_MEMBERS);
        $embedded_ATR_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_embedded_id,
            'embedded AT R',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
        $embedded_ATR_version_id = $this->addEmbeddedVersion($embedded_ATR_id, 'Second !');
        $this->addWritePermissionOnItem($embedded_ATR_id, ProjectUGroup::PROJECT_MEMBERS);

        $embedded_ATE_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_embedded_id,
            'embedded AT E',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
        $embedded_ATE_version_id = $this->addEmbeddedVersion($embedded_ATE_id, 'Third !');
        $this->addWritePermissionOnItem($embedded_ATE_id, ProjectUGroup::PROJECT_MEMBERS);

        $embedded_DIS_AT_id         = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_embedded_id,
            'embedded DIS AT',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
        $embedded_DIS_AT_version_id = $this->addEmbeddedVersion($embedded_DIS_AT_id, ':o !');
        $this->addWritePermissionOnItem($embedded_DIS_AT_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addApprovalTableForFile((int)$embedded_DIS_AT_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);

        $this->createItem(
            self::REGULAR_USER_ID,
            $folder_embedded_id,
            'embedded NO AT',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );

        $embedded_L_id = $this->createItem(
            self::REGULAR_USER_ID,
            $folder_embedded_id,
            'embedded L',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );


        $this->addApprovalTableForFile((int)$embedded_ATC_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addApprovalTableForFile((int)$embedded_ATR_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addApprovalTableForFile((int)$embedded_ATE_version_id, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $this->addReadPermissionOnItem($folder_embedded_id, \ProjectUGroup::PROJECT_ADMIN);

        $this->lockItem($embedded_L_id);

        $this->appendCustomMetadataValueToItem($folder_embedded_id, "custom value for folder_3");

        $this->appendCustomMetadataValueToItem($embedded_ATC_id, "custom value for embedded ATC");
        $this->appendCustomMetadataValueToItem($embedded_ATR_id, "custom value for embedded ATR");
        $this->appendCustomMetadataValueToItem($embedded_ATE_id, "custom value for embedded ATE");
    }

    private function addEmbeddedVersion(int $embedded_ATC_id, string $title)
    {
        $version = [
            'item_id'   => $embedded_ATC_id,
            'number'    => 1,
            'user_id'   => 102,
            'label'     => '',
            'changelog' => '',
            'date'      => time(),
            'filename'  => $title,
            'filesize'  => 3,
            'filetype'  => PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            'path'      => ''
        ];

        $version_factory = new \Docman_VersionFactory();
        return $version_factory->create($version);
    }
}
