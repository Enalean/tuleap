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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest;

use Docman_ItemFactory;
use ProjectUGroup;
use Tuleap\Docman\Test\rest\Helper\DocmanDataBuildCommon;

class DocmanWithMetadataActivatedDataBuilder
{
    public const PROJECT_NAME = 'DocmanProjectMetadata';
    /**
     * @var \Docman_MetadataFactory
     */
    private $metadata_factory;
    /**
     * @var Docman_ItemFactory
     */
    private $docman_factory;
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
        $this->common_builder   = $common_builder;
        $this->docman_user_id   = $this->common_builder->getUserByName(DocmanDataBuildCommon::DOCMAN_REGULAR_USER_NAME);
        $this->docman_factory   = new Docman_ItemFactory();
        $this->metadata_factory = new \Docman_MetadataFactory($this->common_builder->getProject()->getID());
    }

    public function setUp(): void
    {
        echo 'Setup Docman with activated metadata REST Tests configuration' . PHP_EOL;

        $this->setMetadataUsageByLabel('status');
        $this->setMetadataUsageByLabel('obsolescence_date');
        $this->createCustomMetadata();
        $this->common_builder->installPlugin($this->common_builder->getProject());
        $this->addContent();
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *         Root
     *          +
     *          |
     *          +
     *      Folder HM
     *
     * * HM => Hardcoded Metadata
     */
    private function addContent(): void
    {
        $docman_root = $this->docman_factory->getRoot($this->common_builder->getProject()->getID());
        $this->common_builder->addWritePermissionOnItem((int) $docman_root->getId(), ProjectUGroup::PROJECT_MEMBERS);
        $this->createFolderWithHardcodedMetadataItems($docman_root);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                        Folder HM
     *                                            +
     *                                            |
     *                     +----------------------+
     *                     |                      |
     *                     +                      +
     *                  PUT F                  PUT F OD
     *
     * F OD => The file will be updated with Obsolescence Date metadata
     * F => The file will be updated with all hardcoded metadata metadata
     */
    private function createFolderWithHardcodedMetadataItems(\Docman_Item $docman_root): void
    {
        $folder_with_hardcoded_metadata_items_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            (int) $docman_root->getId(),
            'Folder HM',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->common_builder->addWritePermissionOnItem($folder_with_hardcoded_metadata_items_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_with_hardcoded_metadata_items_id,
            'PUT F OD',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_with_hardcoded_metadata_items_id,
            'PUT F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $sub_folder_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_with_hardcoded_metadata_items_id,
            'PUT HM FO',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->common_builder->addWritePermissionOnItem($sub_folder_id, ProjectUGroup::PROJECT_MEMBERS);
    }

    private function setMetadataUsageByLabel(string $label): void
    {
        $settings_dao = new \Docman_SettingsDao();
        $settings_dao->updateMetadataUsageForGroupId($this->common_builder->getProject()->getID(), $label, 1);
    }

    private function createCustomMetadata(): void
    {
        $text_metadata = new \Docman_Metadata();

        $text_metadata->setName("text metadata");
        $text_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $text_metadata->setDescription("");
        $text_metadata->setIsRequired(false);
        $text_metadata->setIsEmptyAllowed(false);
        $text_metadata->setIsMultipleValuesAllowed(false);
        $text_metadata->setSpecial(false);
        $text_metadata->setUseIt(true);

        $this->metadata_factory->create(
            $text_metadata
        );

        $custom_metadata = new \Docman_Metadata();

        $custom_metadata->setName("list metadata");
        $custom_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $custom_metadata->setDescription("");
        $custom_metadata->setIsRequired(false);
        $custom_metadata->setIsEmptyAllowed(true);
        $custom_metadata->setIsMultipleValuesAllowed(false);
        $custom_metadata->setSpecial(false);
        $custom_metadata->setUseIt(true);

        $list_id = $this->metadata_factory->create(
            $custom_metadata
        );

        $love_factory = new \Docman_MetadataListOfValuesElementFactory($list_id);

        $value = new \Docman_MetadataListOfValuesElement();
        $value->setName("value 1");

        $value_two = new \Docman_MetadataListOfValuesElement();
        $value_two->setName("value 2");

        $love_factory->create($value);
        $love_factory->create($value_two);

        $custom_metadata = new \Docman_Metadata();

        $custom_metadata->setName("other list metadata");
        $custom_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $custom_metadata->setDescription("");
        $custom_metadata->setIsRequired(false);
        $custom_metadata->setIsEmptyAllowed(true);
        $custom_metadata->setIsMultipleValuesAllowed(true);
        $custom_metadata->setSpecial(false);
        $custom_metadata->setUseIt(true);

        $list_id = $this->metadata_factory->create(
            $custom_metadata
        );

        $love_factory = new \Docman_MetadataListOfValuesElementFactory($list_id);

        $list_value = new \Docman_MetadataListOfValuesElement();
        $list_value->setName("list A");

        $list_value_two = new \Docman_MetadataListOfValuesElement();
        $list_value_two->setName("list B");

        $love_factory->create($list_value);
        $love_factory->create($list_value_two);
    }
}
