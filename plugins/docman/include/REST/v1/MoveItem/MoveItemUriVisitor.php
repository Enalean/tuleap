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

namespace Tuleap\Docman\REST\v1\MoveItem;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OtherDocument;

/**
 * @template-implements ItemVisitor<string>
 */
final readonly class MoveItemUriVisitor implements ItemVisitor
{
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitFolder(Docman_Folder $item, array $params = []): string
    {
        return '/api/docman_folders/' . urlencode((string) $item->getId());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitWiki(Docman_Wiki $item, array $params = []): string
    {
        return '/api/docman_wikis/' . urlencode((string) $item->getId());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitLink(Docman_Link $item, array $params = []): string
    {
        return '/api/docman_links/' . urlencode((string) $item->getId());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitFile(Docman_File $item, array $params = []): string
    {
        return '/api/docman_files/' . urlencode((string) $item->getId());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): string
    {
        return '/api/docman_embedded_files/' . urlencode((string) $item->getId());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitEmpty(Docman_Empty $item, array $params = []): string
    {
        return '/api/docman_empty_documents/' . urlencode((string) $item->getId());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = []): string
    {
        return $this->dispatcher
            ->dispatch(new MoveOtherItemUriRetriever($item))
            ->getMoveUri();
    }
}
