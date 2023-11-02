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
use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesFullRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesMinimalRepresentation;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Folders\FolderPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;
use Tuleap\Docman\View\DocmanViewURLBuilder;

class ItemRepresentationVisitor implements ItemVisitor
{
    public function __construct(
        private ItemRepresentationBuilder $item_representation_builder,
        private \Docman_VersionFactory $docman_version_factory,
        private \Docman_LinkVersionFactory $docman_link_version_factory,
        private \Docman_ItemFactory $item_factory,
        private \EventManager $event_manager,
        DocmanItemsEventAdder $event_adder,
    ) {
        $event_adder->addLogEvents();
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_FOLDER,
            null,
            null,
            null,
            null,
            $this->buildFolderProperties($item, $params)
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

            $wiki_representation = WikiPropertiesRepresentation::build($item, $wiki_page_id);
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
            $version         = null;
            if ($item->getCurrentVersion()) {
                $version = $item->getCurrentVersion()->getNumber();
            }

            $this->event_manager->processEvent(
                'plugin_docman_event_access',
                [
                    'group_id' => $item->getGroupId(),
                    'item'     => $item,
                    'version'  => $version,
                    'user'     => $params['current_user'],
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
            $download_href   = $this->buildFileDirectAccessURL($item);
            $open_item_href  = $this->event_manager->dispatch(new OpenItemHref($item, $item_version));
            $file_properties = FilePropertiesRepresentation::build($item_version, $download_href, $open_item_href->getHref());
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
                    'user'     => $params['current_user'],
                ]
            );
        }

        $item_version             = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_embedded_properties = null;
        if ($item_version) {
            $content = file_get_contents($item_version->getPath());
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

    private function buildFileDirectAccessURL(Docman_Item $item): string
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
        if (! $latest_link_version) {
            return LinkPropertiesRepresentation::build(null);
        }

        return LinkPropertiesRepresentation::build($latest_link_version);
    }

    private function isADirectAccessToDocument(array $params): bool
    {
        return isset($params['is_a_direct_access']) && (bool) $params['is_a_direct_access'] === true;
    }

    private function buildFolderProperties(Docman_Folder $item, array $params): ?FolderPropertiesRepresentation
    {
        if (! isset($params['with_size']) || $params['with_size'] === false) {
            return null;
        }

        $this->item_factory->getItemTree(
            $item,
            $params['current_user'],
            false,
            true
        );

        return FolderPropertiesRepresentation::build($item);
    }
}
