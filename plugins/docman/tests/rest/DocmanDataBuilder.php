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
        $this->setFeatureFlag();
    }

    private function installPlugin()
    {
        $plugin_manager = PluginManager::instance();
        $plugin_manager->installAndActivate('docman');
        $this->activateWikiServiceForTheProject();
    }

    private function addItem($user_id, $docman_root_id, $title, $item_type, $link_url = '', $file_path = '', $wiki_page = '')
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

        $item_id = $this->docman_item_factory->create($item, 1);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                $file_type = 'text/html';
                $this->addItemVersion($item_id, $title, $file_type, $file_path);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $file_type = 'application/pdf';
                $this->addItemVersion($item_id, $title, $file_type);
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

    private function addItemVersion($item_id, $title, $item_type, $file_path = '')
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
        $version_factory->create($version);
    }

    private function addLinkVersion($item_id)
    {
        $docman_factory = new Docman_ItemFactory();
        $docman_link    = $docman_factory->getItemFromDb($item_id);
        $docman_link->setUrl('https://my.example.test');
        $version_link_factory = new \Docman_LinkVersionFactory();
        $version_link_factory->create($docman_link, 'changset1', 'test rest Change', time());
    }


    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                    Root
     *                                     +
     *                                     |
     *                            +--------+---------+---------+---------+
     *                            |                  |         |         |
     *                            +                  +         +         +
     *                          folder 1           folder 3   file A    file B
     *                            +
     *                            |
     *  +---------------+---------+--------+---------------------+---------------------+-------------+-----------+
     *  +               +                  +                     +                     +             +           +
     *Item A          Item B             Item C             Folder 2                 Item E        Item F      Item G
     *                                                           +
     *                                                           |
     *                                                           +
     *                                                        Item D
     */
    private function addContent()
    {
        $docman_root = $this->docman_item_factory->getRoot($this->project->getID());

        $folder_id   = $this->addItem(self::REGULAR_USER_ID, $docman_root->getId(), 'folder 1', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $folder_3_id = $this->addItem(self::REGULAR_USER_ID, $docman_root->getId(), 'folder 3', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $this->addWritePermissionOnItem($folder_id, \ProjectUGroup::PROJECT_MEMBERS);

        $file_A_id = $this->addItem(self::REGULAR_USER_ID, $folder_id, 'file A', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $this->addWritePermissionOnItem($file_A_id, \ProjectUGroup::PROJECT_MEMBERS);
        $file_B_id = $this->addItem(self::REGULAR_USER_ID, $folder_id, 'file B', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $this->addWritePermissionOnItem($file_B_id, \ProjectUGroup::PROJECT_MEMBERS);

        $item_A_id = $this->addItem(self::ANON_ID, $folder_id, 'item A', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
        $item_B_id = $this->addItem(self::REGULAR_USER_ID, $folder_id, 'item B', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);
        $item_C_id = $this->addItem(self::REGULAR_USER_ID, $folder_id, 'item C', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $folder_2_id = $this->addItem(self::REGULAR_USER_ID, $folder_id, 'folder 2', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);


        $item_D_id = $this->addItem(self::REGULAR_USER_ID, $folder_2_id, 'item D', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);

        $item_E_id = $this->addItem(
            self::REGULAR_USER_ID,
            $folder_id,
            'item E',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            "https://example.test"
        );

        $item_F_path = dirname(__FILE__) . '/_fixtures/docmanFile/embeddedFile';
        $item_F_id = $this->addItem(
            self::REGULAR_USER_ID,
            $folder_id,
            'item F',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            '',
            $item_F_path
        );

        $item_G_id = $this->addItem(self::REGULAR_USER_ID, $folder_id, 'item G', PLUGIN_DOCMAN_ITEM_TYPE_WIKI, '', '', 'MyWikiPage');

        $this->addReadPermissionOnItem($item_A_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($item_B_id, \ProjectUGroup::PROJECT_ADMIN);
        $this->addReadPermissionOnItem($item_C_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($folder_2_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($item_D_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($item_E_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($item_F_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($folder_3_id, \ProjectUGroup::PROJECT_ADMIN);
        $this->addReadPermissionOnItem($item_G_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->addApprovalTableForItem($folder_id);
        $this->lockItem($folder_3_id);


        $this->appendCustomMetadataValueToItem($item_A_id, "custom value for item_A");
        $this->appendCustomMetadataValueToItem($item_B_id, "custom value for item_B");
        $this->appendCustomMetadataValueToItem($item_C_id, "custom value for item_C");
        $this->appendCustomMetadataValueToItem($folder_2_id, "custom value for folder_2");
        $this->appendCustomMetadataValueToItem($item_D_id, "custom value for item_D");
        $this->appendCustomMetadataValueToItem($item_E_id, "custom value for item_E");
        $this->appendCustomMetadataValueToItem($item_F_id, "custom value for item_F");
        $this->appendCustomMetadataValueToItem($folder_3_id, "custom value for folder_3");
        $this->appendCustomMetadataValueToItem($item_G_id, "custom value for item_G");
        $this->appendCustomMetadataValueToItem($file_A_id, "custom value for file A");
        $this->appendCustomMetadataValueToItem($file_B_id, "custom value for file B");
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
        $initializer = new DocmanDatabaseInitialization();
        $initializer->setup($this->project);
    }

    private function createCustomMetadata() : void
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

    private function appendCustomMetadataValueToItem(int $item_id, string $value) : void
    {
        $metadata_value  = new \Docman_MetadataValueScalar();

        $metadata_value->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata_value->setItemId($item_id);
        $metadata_value->setFieldId(1);
        $metadata_value->setValueString($value);

        $this->metadata_value_factory->create($metadata_value);
    }

    private function addApprovalTableForItem(int $id): void
    {
        $dao = new Docman_ApprovalTableItemDao();
        $dao->createTable(
            'item_id',
            $id,
            self::REGULAR_USER_ID,
            "",
            time(),
            PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED,
            false
        );
    }

    private function setFeatureFlag()
    {
        $local_inc = fopen('/etc/tuleap/conf/local.inc', 'a');
        if ($local_inc === false) {
            throw new \RuntimeException('Could not append feature flag to local.inc');
        }
        $write_result = fwrite(
            $local_inc,
            "\$enable_patch_item_route = 1;"
        );
        if ($write_result === false) {
            throw new \RuntimeException('Could not append feature flag to local.inc');
        }
        fclose($local_inc);
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
}
