<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Document\RecentlyVisited;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_VersionFactory;
use Docman_Wiki;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Docman\Item\OtherDocument;

/**
 * @implements ItemVisitor<string>
 */
final readonly class VisitedDocumentHrefVisitor implements ItemVisitor
{
    public function __construct(
        private Docman_VersionFactory $docman_version_factory,
        private EventDispatcherInterface $event_manager,
    ) {
    }

    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitLink(Docman_Link $item, array $params = [])
    {
        return '/plugins/docman/?' . http_build_query([
            'group_id' => $item->getGroupId(),
            'action'   => 'show',
            'id'       => $item->getId(),
        ]);
    }

    #[\Override]
    public function visitFile(Docman_File $item, array $params = [])
    {
        $item_version = $this->docman_version_factory->getCurrentVersionForItem($item);
        if ($item_version) {
            $open_item_href = $this->event_manager->dispatch(new OpenItemHref($item, $item_version))->getHref();
            if ($open_item_href !== null) {
                return $open_item_href;
            }
        }

        return '/plugins/docman/download/' . urlencode((string) $item->getId());
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = [])
    {
        return '/plugins/document/'
            . urlencode($params['project']->getUnixNameMixedCase())
            . '/preview/'
            . urlencode((string) $item->getId());
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = [])
    {
        $other_document_href = $this->event_manager->dispatch(new VisitedOtherDocumentHref($item))->getHref();

        return $other_document_href ?? $this->visitItem($item, $params);
    }
}
