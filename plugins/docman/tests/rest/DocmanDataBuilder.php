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

use Tuleap\Docman\Test\rest\Helper\DocmanDataBuildCommon;
use Tuleap\Docman\Test\rest\Helper\DocmanEmbeddedDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanEmptyDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanFileDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanFolderDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanLinkDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanWikiDataBuild;

class DocmanDataBuilder extends DocmanDataBuildCommon
{
    public const PROJECT_NAME = 'DocmanProject';

    public function setUp(): void
    {
        echo 'Setup Docman REST Tests configuration' . PHP_EOL;

        $this->installPlugin($this->project);
        $this->generateDocmanRegularUser();
        $this->addContent();
    }


    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *         Root
     *          +
     *          |
     *    +-----+-----+--------------+--------+-------+-------+
     *    |           |              |        |       |       |
     *    +           +              +        +       +       +
     *  Folder       File         Embedded   Link   Wiki     Empty
     *    +           +              +        +       +       +
     *    |           |              |        |       |       |
     *   ...         ...            ...      ...     ...     ...
     */
    private function addContent(): void
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

        $empty_builder = new DocmanEmptyDataBuild($common_builder);
        $empty_builder->createEmptyWithContent($docman_root);

        $folder_builder = new DocmanFolderDataBuild($common_builder);
        $folder_builder->createFolderWithContent($docman_root);
    }
}
