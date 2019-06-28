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

use ProjectUGroup;
use Tuleap\Docman\Test\rest\Helper\DocmanDataBuildCommon;

class DocmanWithMetadataActivatedDataBuilder extends DocmanDataBuildCommon
{
    public const PROJECT_NAME = 'DocmanProjectMetadata';

    /**
     * @var \Docman_SettingsDao
     */
    private $settings_dao;

    public function setUp(): void
    {
        echo 'Setup Docman with activated metadata REST Tests configuration' . PHP_EOL;

        $this->setMetadataUsageByLabel('status');
        $this->setMetadataUsageByLabel('obsolescence_date');
        $this->installPlugin($this->project);
        $this->addContent();
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *         Root
     *          +
     *          |
     *    +-----+
     *    |
     *    +
     *  Folder HM
     *    +
     *    |
     *   ...
     *
     * * HM => Hardcoded Metadata
     */
    private function addContent(): void
    {
        $docman_root = $this->docman_item_factory->getRoot($this->project->getID());
        $this->addWritePermissionOnItem($docman_root->getId(), ProjectUGroup::PROJECT_MEMBERS);
        $this->createFolderWithHardcodedMetadataItems($docman_root);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                                        Folder HM
     *                                            +
     *                                            |
     *                     +----------------------+-------------------+
     *                     |                      |                   |
     *                     +                      +                   +
     *                  PUT F            PUT F OD          PUT F S
     *
     * F OD => The file will be updated with Obsolescence Date metadata
     * F S => The file will be updated with Status metadata
     * F => The file will be updated with all hardcoded metadata metadata
     */
    private function createFolderWithHardcodedMetadataItems(\Docman_Item $docman_root): void
    {
        $folder_with_hardcoded_metadata_items_id = $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $docman_root->getId(),
            'Folder HM',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );

        $this->addWritePermissionOnItem($folder_with_hardcoded_metadata_items_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_with_hardcoded_metadata_items_id,
            'PUT F S',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_with_hardcoded_metadata_items_id,
            'PUT F OD',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );

        $this->createItemWithVersion(
            self::REGULAR_USER_ID,
            $folder_with_hardcoded_metadata_items_id,
            'PUT F',
            PLUGIN_DOCMAN_ITEM_TYPE_FILE
        );
    }

    private function setMetadataUsageByLabel(string $label): void
    {
        $this->settings_dao = new \Docman_SettingsDao();
        $this->settings_dao->updateMetadataUsageForGroupId($this->project->getID(), $label, 1);
    }
}
