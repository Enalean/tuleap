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

class DocmanEmptyDataBuild
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
     *     Empty
     *      |
     *      +
     *  DELETE Empty
     *
     */
    public function createEmptyWithContent($docman_root): void
    {
        $folder_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $docman_root->getId(),
            'Empty',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->createDeleteFolder($folder_id);
        $this->createLockFolder($folder_id);
        $this->createPutFolder($folder_id);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        DELETE Empty
     *                           +
     *                           |
     *                           +
     *                  +----------------+---------------+
     *                  |                |               |
     *                  +                +               +
     *              DELETE EM       DELETE EM L       DELETE EM RO
     *
     */
    private function createDeleteFolder(int $folder_id): void
    {
        $folder_delete_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'DELETE Empty',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_delete_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_delete_id,
            'DELETE EM',
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $this->common_builder->createAndLockItem($folder_delete_id, $this->admin_user_id, $this->admin_user_id, 'DELETE EM L', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $this->common_builder->createAdminOnlyItem($folder_delete_id, 'DELETE EM RO', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        LOCK Empty
     *                           +
     *                           |
     *                           +
     *                        LOCK EM
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
            'LOCK Empty',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_lock_id, \ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_lock_id,
            'LOCK EM',
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                        PUT HM Empty
     *                           +
     *                           |
     *                           +
     *                        PUT EM
     */
    private function createPutFolder(int $folder_id): void
    {
        $folder_put_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'PUT HM Empty',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_put_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_put_id,
            'PUT EM',
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );
    }
}
