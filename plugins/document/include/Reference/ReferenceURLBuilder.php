<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Reference;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Project\ProjectByIDFactory;

/**
 * @template-implements ItemVisitor<string>
 */
final class ReferenceURLBuilder implements ItemVisitor
{
    public function __construct(
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly ProjectByIDFactory $project_factory,
    ) {
    }

    public function buildURLForReference(
        \Docman_Item $docman_item,
        string $base_reference_link,
    ): string {
        if (! str_starts_with($base_reference_link, '/plugins/docman/')) {
            return $base_reference_link;
        }

        return $docman_item->accept(
            $this,
            [
                'base_reference_link' => $base_reference_link,
            ],
        );
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        $project = $this->project_factory->getProjectById((int) $item->getGroupId());

        return '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/folder/$1';
    }

    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        return $params['base_reference_link'] ?? '';
    }

    public function visitLink(Docman_Link $item, array $params = [])
    {
        return $params['base_reference_link'] ?? '';
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        $open_item_href = $this->event_dispatcher->dispatch(
            new OpenItemHref(
                $item,
                $item->getCurrentVersion(),
            )
        );

        $open_item_href_link = $open_item_href->getHref();
        if ($open_item_href_link !== null) {
            return $open_item_href_link;
        }

        return $params['base_reference_link'] ?? '';
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        $project = $this->project_factory->getProjectById((int) $item->getGroupId());

        return '/plugins/document/' .
            urlencode($project->getUnixNameLowerCase()) .
            '/folder/' .
            urlencode((string) $item->getParentId()) .
            '/$1';
    }

    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        $project = $this->project_factory->getProjectById((int) $item->getGroupId());

        return '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/preview/$1';
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return $params['base_reference_link'] ?? '';
    }
}
