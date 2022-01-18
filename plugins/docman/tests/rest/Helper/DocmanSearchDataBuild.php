<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Helper;

use ProjectUGroup;

final class DocmanSearchDataBuild
{
    private int $docman_user_id;

    public function __construct(private DocmanDataBuildCommon $common_builder)
    {
        $this->docman_user_id = $this->common_builder->getUserByName(DocmanDataBuildCommon::DOCMAN_REGULAR_USER_NAME);
    }
    /**
     * To help understand tests structure, below a representation of search hierarchy
     *
     *                Search
     *                  +
     *                  |
     *                  +
     *      +-----------+
     *      |           |
     *      +           +
     *   foo.txt      bar.txt
     *
     *
     */
    public function createSearchContent(?\Docman_Item $docman_root): void
    {
        if (! $docman_root) {
            return;
        }

        $folder_id = $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            (int) $docman_root->getId(),
            'Search',
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
        $this->common_builder->addWritePermissionOnItem($folder_id, ProjectUGroup::PROJECT_MEMBERS);

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'foo.txt',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            "https://example.test"
        );

        $this->common_builder->createItemWithVersion(
            $this->docman_user_id,
            $folder_id,
            'bar.txt',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
        );
    }
}
