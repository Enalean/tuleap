<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Docman\View\DocmanViewURLBuilder;

/**
 * @template-implements ItemVisitor<FilePropertiesRepresentation | null>
 */
final class FilePropertiesVisitor implements ItemVisitor
{
    public function __construct(
        private \Docman_VersionFactory $version_factory,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function visitFolder(\Docman_Folder $item, array $params = [])
    {
        return null;
    }

    public function visitWiki(\Docman_Wiki $item, array $params = [])
    {
        return null;
    }

    public function visitLink(\Docman_Link $item, array $params = [])
    {
        return null;
    }

    public function visitFile(\Docman_File $item, array $params = [])
    {
        $version = $this->version_factory->getCurrentVersionForItem($item);
        if (! $version) {
            return null;
        }

        $download_href  = $this->buildFileDirectAccessURL($item, $version);
        $open_item_href = $this->event_dispatcher->dispatch(new OpenItemHref($item, $version));
        return FilePropertiesRepresentation::build($version, $download_href, $open_item_href->getHref());
    }

    private function buildFileDirectAccessURL(\Docman_Item $item, \Docman_Version $version): string
    {
        return DocmanViewURLBuilder::buildActionUrl(
            $item,
            ['default_url' => '/plugins/docman/?'],
            [
                'action'         => 'show',
                'switcholdui'    => 'true',
                'group_id'       => $item->getGroupId(),
                'id'             => $item->getId(),
                'version_number' => $version->getNumber(),
            ],
            true
        );
    }

    public function visitEmbeddedFile(\Docman_EmbeddedFile $item, array $params = [])
    {
        return null;
    }

    public function visitEmpty(\Docman_Empty $item, array $params = [])
    {
        return null;
    }

    public function visitItem(\Docman_Item $item, array $params = [])
    {
        return null;
    }
}
