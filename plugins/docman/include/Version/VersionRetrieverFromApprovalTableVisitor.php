<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Version;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_VersionFactory;
use Docman_Wiki;
use LogicException;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\PHPWiki\WikiPage;
use WikiPageVersion;
use WikiPageVersionFactory;
use WikiVersionDao;

/**
 * @implements ItemVisitor<Version|null|WikiPageVersion>
 */
final readonly class VersionRetrieverFromApprovalTableVisitor implements ItemVisitor
{
    public function __construct(private Docman_VersionFactory $version_factory, private \Docman_LinkVersionFactory $link_version_factory, private WikiVersionDao $wiki_version_dao, private WikiPageVersionFactory $wiki_version_factory)
    {
    }

    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = []): Version
    {
        throw new LogicException('Folder are not versioned');
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        if (! isset($params['approval_table_version_number'])) {
            throw new LogicException('Version number not provided for Wiki');
        }

        $wiki_page = new WikiPage($item->getId());

        $version = $this->wiki_version_dao->getSpecificVersionForGivenPage(
            $wiki_page->getId(),
            $params['approval_table_version_number']
        );

        if (! $version || $version->count() === 0) {
            return null;
        }

        return $this->wiki_version_factory->getInstanceFromRow($version->getRow());
    }

    #[\Override]
    public function visitLink(Docman_Link $item, array $params = []): Version
    {
        if (! isset($params['approval_table_version_number'])) {
            throw new LogicException('Version number not provided for link');
        }

        $version = $this->link_version_factory->getSpecificVersion($item, $params['approval_table_version_number']);
        if ($version === null) {
            throw new LogicException('Link does not have a version');
        }

        return $version;
    }

    #[\Override]
    public function visitFile(Docman_File $item, array $params = []): Version
    {
        if (! isset($params['approval_table_version_number'])) {
            throw new LogicException('Version number not provided for file');
        }

        $version = $this->version_factory->getSpecificVersion($item, $params['approval_table_version_number']);
        if ($version === null) {
            throw new LogicException('File does not have a version');
        }

        return $version;
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): Version
    {
        if (! isset($params['approval_table_version_number'])) {
            throw new LogicException('Version number not provided for embedded file');
        }

        $version = $this->version_factory->getSpecificVersion($item, $params['approval_table_version_number']);
        if ($version === null) {
            throw new LogicException('Embedded file does not have a version');
        }

        return $version;
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = []): Version
    {
        throw new LogicException('Empty are not versioned');
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): Version
    {
        throw new LogicException('Item without type are not versioned');
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = []): Version
    {
        throw new LogicException('Other document are not versioned yet');
    }
}
