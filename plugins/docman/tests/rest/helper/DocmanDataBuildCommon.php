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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

namespace Tuleap\Docman\rest\v1;

use Docman_ItemFactory;
use Docman_MetadataValueFactory;
use PluginManager;
use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanDatabaseInitialization;

class DocmanDataBuildCommon extends REST_TestDataBuilder
{
    const DOCMAN_REGULAR_USER_NAME = 'docman_regular_user';
    const REGULAR_USER_ID          = 102;

    private const DOCMAN_REGULAR_USER_PASSWORD = 'welcome0';


    /**
     * @var \Docman_ItemFactory
     */
    protected $docman_item_factory;

    /**
     * @var \Project
     */
    protected $project;
    /**
     * @var \PFUser
     */
    private $docman_user;
    /**
     * @var \Docman_MetadataFactory
     */
    private $metadata_factory;
    /**
     * @var Docman_MetadataValueFactory
     */
    private $metadata_value_factory;


    public function __construct(string $project_name)
    {
        parent::__construct();
        $this->project             = $this->project_manager->getProjectByUnixName($project_name);
        $this->docman_item_factory = Docman_ItemFactory::instance($this->project->getID());
        $this->installPlugin($this->project);
        $this->metadata_factory       = new \Docman_MetadataFactory($this->project->getID());
        $this->metadata_value_factory = new Docman_MetadataValueFactory($this->project->getID());
    }

    /**
     * @return bool|int
     */
    protected function createItemWithVersion(
        int $user_id,
        int $docman_root_id,
        string $title,
        int $item_type,
        string $link_url = '',
        string $file_path = '',
        string $wiki_page = ''
    ) {
        $item_id = $this->createItem($user_id, $docman_root_id, $title, $item_type, $link_url, $wiki_page);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                $file_type = 'text/html';
                $this->addFileVersion($item_id, $title, $file_type, $file_path);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $file_type = 'application/pdf';
                $this->addFileVersion($item_id, $title, $file_type, "");
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

    /**
     * @return bool|int
     */
    protected function createItem(
        int $user_id,
        int $docman_root_id,
        string $title,
        int $item_type,
        string $link_url = '',
        string $wiki_page = ''
    ) {
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

        return $this->docman_item_factory->create($item, 1);
    }

    /**
     * @return bool|int
     */
    protected function addFileVersion(int $item_id, string $title, string $item_type, string $file_path)
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

    protected function addLinkVersion(int $item_id): int
    {
        $docman_factory = new Docman_ItemFactory();
        $docman_link    = $docman_factory->getItemFromDb($item_id);
        $docman_link->setUrl('https://my.example.test');
        $version_link_factory = new \Docman_LinkVersionFactory();
        $version_link_factory->create($docman_link, 'changset1', 'test rest Change', time());
        $link_version = $version_link_factory->getLatestVersion($docman_link);
        return $link_version->getId();
    }

    protected function addLinkWithCustomVersionNumber(int $item_id, $version): int
    {
        $docman_factory = new Docman_ItemFactory();
        $docman_link    = $docman_factory->getItemFromDb($item_id);
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
        return $link_version->getId();
    }

    protected function generateDocmanRegularUser(): void
    {
        $this->docman_user = $this->user_manager->getUserByUserName(self::DOCMAN_REGULAR_USER_NAME);
        $this->docman_user->setPassword(self::DOCMAN_REGULAR_USER_PASSWORD);
        $this->user_manager->updateDb($this->docman_user);
    }

    protected function installPlugin(\Project $project): void
    {
        $plugin_manager = PluginManager::instance();
        $plugin_manager->installAndActivate('docman');
        $this->activateWikiServiceForTheProject($project->getUnixName());
    }

    protected function addReadPermissionOnItem(int $object_id, int $ugroup_name): void
    {
        permission_add_ugroup(
            $this->project->getID(),
            'PLUGIN_DOCMAN_READ',
            $object_id,
            $ugroup_name,
            true
        );
    }

    protected function addWritePermissionOnItem(int $object_id, int $ugroup_name): void
    {
        permission_add_ugroup(
            $this->project->getID(),
            'PLUGIN_DOCMAN_WRITE',
            $object_id,
            $ugroup_name,
            true
        );
    }

    protected function createCustomMetadata(): void
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

    protected function appendCustomMetadataValueToItem(int $item_id, string $value): void
    {
        $metadata_value = new \Docman_MetadataValueScalar();

        $metadata_value->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata_value->setItemId($item_id);
        $metadata_value->setFieldId(1);
        $metadata_value->setValueString($value);

        $this->metadata_value_factory->create($metadata_value);
    }

    private function activateWikiServiceForTheProject(string $project_name): void
    {
        $this->project = $this->project_manager->getProjectByUnixName($project_name);
        $initializer   = new DocmanDatabaseInitialization();
        $initializer->setup($this->project);
    }
}
