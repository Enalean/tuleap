<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesRepresentation;
use Tuleap\Docman\View\DocmanViewURLBuilder;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;

class ItemRepresentationVisitor implements ItemVisitor
{
    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;

    /**
     * @var \Docman_VersionFactory
     */
    private $docman_version_factory;

    /**
     * @var \Docman_LinkVersionFactory
     */
    private $docman_link_version_factory;

    public function __construct(
        ItemRepresentationBuilder $item_representation_builder,
        \Docman_VersionFactory $docman_version_factory,
        \Docman_LinkVersionFactory $docman_link_version_factory
    ) {
        $this->item_representation_builder = $item_representation_builder;
        $this->docman_version_factory      = $docman_version_factory;
        $this->docman_link_version_factory = $docman_link_version_factory;
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_FOLDER,
            null,
            null,
            null
        );
    }

    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        $wiki_representation = null;
        if ($item->getPagename() !== null) {
            $wiki_representation = new WikiPropertiesRepresentation();
            $wiki_html_url       = $this->buildDirectAccessURL($item);
            $wiki_representation->build($item, $wiki_html_url);
        }
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_WIKI,
            null,
            null,
            null,
            $wiki_representation
        );
    }

    public function visitLink(Docman_Link $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_LINK,
            null,
            null,
            $this->buildLinkProperties($item),
            null
        );
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        $item_version    = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_properties = null;
        if ($item_version) {
            $file_properties = new FilePropertiesRepresentation();
            $download_url    = $this->buildDirectAccessURL($item);
            $file_properties->build($item_version, $download_url);
        }
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_FILE,
            $file_properties,
            null,
            null,
            null
        );
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        $item_version             = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_embedded_properties = null;
        if ($item_version) {
            $file_embedded_properties = new EmbeddedFilePropertiesRepresentation();
            $content                  = file_get_contents($item_version->getPath());
            $file_embedded_properties->build($item_version, $content);
        }
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_EMBEDDED,
            null,
            $file_embedded_properties,
            null,
            null
        );
    }

    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_EMPTY,
            null,
            null,
            null,
            null
        );
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation($item, null, null);
    }

    private function buildDirectAccessURL(Docman_Item $item) : string
    {
        return DocmanViewURLBuilder::buildActionUrl(
            $item,
            ['default_url' => '/plugins/docman/?'],
            ['action' => 'show', 'switcholdui' => 'true', 'group_id' => $item->getGroupId(), 'id' => $item->getId()],
            true
        );
    }

    /**
     * @param Docman_Link $item
     *
     * @return LinkPropertiesRepresentation
     */
    private function buildLinkProperties(Docman_Link $item): LinkPropertiesRepresentation
    {
        $latest_link_version = $this->docman_link_version_factory->getLatestVersion($item);
        $link_properties     = new LinkPropertiesRepresentation();
        if (! $latest_link_version) {
            $link_properties->build($item->getUrl(), null);
            return $link_properties;
        }

        $link_properties->build($this->buildDirectAccessURL($item), $latest_link_version);

        return $link_properties;
    }
}
