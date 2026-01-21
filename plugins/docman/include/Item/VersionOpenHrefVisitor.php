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

namespace Tuleap\Docman\Item;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Version;
use Docman_Wiki;
use LogicException;
use Project;

/**
 * @implements ItemVisitor<string>
 */
final readonly class VersionOpenHrefVisitor implements ItemVisitor
{
    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = []): string
    {
        return '';
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = []): string
    {
        return '';
    }

    #[\Override]
    public function visitLink(Docman_Link $item, array $params = []): string
    {
        if (! isset($params['version']) || ! $params['version'] instanceof \Docman_LinkVersion) {
            throw new LogicException('No version provided for link');
        }

        return '/plugins/docman/?'
            . http_build_query(
                [
                    'group_id'       => $item->getGroupId(),
                    'action'         => 'show',
                    'id'             => $item->getId(),
                    'version_number' => $params['version']->getNumber(),
                ]
            );
    }

    #[\Override]
    public function visitFile(Docman_File $item, array $params = []): string
    {
        if (! isset($params['version']) || ! $params['version'] instanceof Docman_Version) {
            throw new LogicException('No version provided for file');
        }

        return '/plugins/docman/download/' . urlencode((string) $item->getId()) . '/' . urlencode((string) $params['version']->getNumber());
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): string
    {
        if (! isset($params['version']) || ! $params['version'] instanceof Docman_Version) {
            throw new LogicException('No version provided for embedded');
        }

        if (! isset($params['project']) || ! ($params['project'] instanceof Project)) {
            throw new LogicException('File does not have a belong to a project');
        }

        return '/plugins/document/' . urlencode($params['project']->getUnixNameLowerCase())
            . '/folder/' . urlencode((string) $item->getParentId())
            . '/' . urlencode((string) $item->getId())
            . '/' . urlencode((string) $params['version']->getId());
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = []): string
    {
        return '';
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): string
    {
        return '';
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = []): string
    {
        return '';
    }
}
