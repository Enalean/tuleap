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
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesFullRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesMinimalRepresentation;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;
use Tuleap\Docman\View\DocmanViewURLBuilder;

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

    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(
        ItemRepresentationBuilder $item_representation_builder,
        \Docman_VersionFactory $docman_version_factory,
        \Docman_LinkVersionFactory $docman_link_version_factory,
        \Docman_ItemFactory $item_factory,
        \EventManager $event_manager
    ) {
        $this->item_representation_builder = $item_representation_builder;
        $this->docman_version_factory      = $docman_version_factory;
        $this->docman_link_version_factory = $docman_link_version_factory;
        $this->item_factory                = $item_factory;
        $this->event_manager               = $event_manager;
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
            $wiki_page_id = $this->item_factory->getIdInWikiOfWikiPageItem(
                $item->getPagename(),
                $item->getGroupId()
            );

            $wiki_representation = new WikiPropertiesRepresentation();
            $wiki_representation->build($item, $wiki_page_id);
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
        $link_properties = null;
        if ($this->isADirectAccessToDocument($params)) {
            $link_properties = $this->buildLinkProperties($item);
            $version = null;
            if ($item->getCurrentVersion()) {
                $version = $item->getCurrentVersion()->getNumber();
            }

            $this->event_manager->processEvent(
                'plugin_docman_event_access',
                [
                    'group_id' => $item->getGroupId(),
                    'item'     => $item,
                    'version'  => $version,
                    'user'     => $params['current_user']
                ]
            );
        }

        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_LINK,
            null,
            null,
            $link_properties,
            null
        );
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        $item_version    = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_properties = null;
        if ($item_version) {
            $file_properties = new FilePropertiesRepresentation();
            $download_href    = $this->buildFileDirectAccessURL($item);
            $file_properties->build($item_version, $download_href);
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
        if ($this->isADirectAccessToDocument($params)) {
            $version = null;
            if ($item->getCurrentVersion()) {
                $version = $item->getCurrentVersion()->getNumber();
            }

            $this->event_manager->processEvent(
                'plugin_docman_event_access',
                [
                    'group_id' => $item->getGroupId(),
                    'item'     => $item,
                    'version'  => $version,
                    'user'     => $params['current_user']
                ]
            );
        }

        $item_version             = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_embedded_properties = null;
        if ($item_version) {
            $content                  = file_get_contents($item_version->getPath());
            if ($this->isADirectAccessToDocument($params)) {
                $file_embedded_properties = EmbeddedFilePropertiesFullRepresentation::build($item_version, $content);
            } else {
                $file_embedded_properties = EmbeddedFilePropertiesMinimalRepresentation::build($item_version);
            }
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

    private function buildFileDirectAccessURL(Docman_Item $item) : string
    {
        $parameters = ['action' => 'show', 'switcholdui' => 'true', 'group_id' => $item->getGroupId(), 'id' => $item->getId()];
        $version    = $this->docman_version_factory->getCurrentVersionForItem($item);
        if ($version) {
            $parameters['version_number'] = $version->getNumber();
        }

        return DocmanViewURLBuilder::buildActionUrl(
            $item,
            ['default_url' => '/plugins/docman/?'],
            $parameters,
            true
        );
    }

    private function buildLinkProperties(Docman_Link $item): LinkPropertiesRepresentation
    {
        $latest_link_version = $this->docman_link_version_factory->getLatestVersion($item);
        $link_properties     = new LinkPropertiesRepresentation();
        if (! $latest_link_version) {
            $link_properties->build(null);
            return $link_properties;
        }

        $link_properties->build($latest_link_version);

        return $link_properties;
    }

    private function isADirectAccessToDocument(array $params): bool
    {
        return isset($params['is_a_direct_access']) && (bool) $params['is_a_direct_access'] === true;
    }
}
