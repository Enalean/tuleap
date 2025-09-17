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

use Tuleap\Docman\Test\rest\Helper\DocmanProjectBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanEmbeddedDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanEmptyDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanFileDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanFolderDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanLinkDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanSearchDataBuild;
use Tuleap\Docman\Test\rest\Helper\DocmanWikiDataBuild;

class DocmanDataBuilder
{
    public const string PROJECT_NAME = 'DocmanProject';

    public const string DOCMAN_REGULAR_USER_NAME = 'docman_regular_user';

    public function __construct(private DocmanProjectBuilder $project_builder)
    {
    }

    public function setUp(): void
    {
        echo 'Setup Docman REST Tests configuration' . PHP_EOL;

        $this->project_builder->activateWikiServiceForTheProject();
        $this->project_builder->generateDocmanRegularUser();
        $this->addContent();
    }

    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *         Root
     *          +
     *          |
     *    +-----+-----+----------+--------+-------+-------+--------------+-------------------+
     *    |           |          |        |       |       |              |                   |
     *    +           +          +        +       +       +              +                   +
     *  Folder       File     Embedded   Link   Wiki     Empty  Download me as a zip       Search
     *    +           +          +        +       +       +              +                   +
     *    |           |          |        |       |       |              |                   |
     *   ...         ...        ...      ...     ...     ...            ...                 ...
     */
    private function addContent(): void
    {
        $docman_root = $this->project_builder->getRoot();

        $file_builder = new DocmanFileDataBuild($this->project_builder);
        $file_builder->createFolderFileWithContent($docman_root);

        $file_builder = new DocmanEmbeddedDataBuild($this->project_builder);
        $file_builder->createEmbeddedFileWithContent($docman_root);

        $link_builder = new DocmanLinkDataBuild($this->project_builder);
        $link_builder->createLinkFileWithContent($docman_root);

        $wiki_builder = new DocmanWikiDataBuild($this->project_builder);
        $wiki_builder->createWikiWithContent($docman_root);

        $empty_builder = new DocmanEmptyDataBuild($this->project_builder);
        $empty_builder->createEmptyWithContent($docman_root);

        $folder_builder = new DocmanFolderDataBuild($this->project_builder);
        $folder_builder->createFolderWithContent($docman_root);

        $folder_to_download_builder = new DocmanFolderDataBuild($this->project_builder);
        $folder_to_download_builder->createFolderToDownload($docman_root);

        $search_builder = new DocmanSearchDataBuild($this->project_builder);
        $search_builder->createSearchContent($docman_root);
    }
}
