<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

    public function __construct(
        ItemRepresentationBuilder $item_representation_builder,
        \Docman_VersionFactory $docman_version_factory
    ) {
        $this->item_representation_builder    = $item_representation_builder;
        $this->docman_version_factory         = $docman_version_factory;
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            ItemRepresentation::TYPE_FOLDER,
            null
        );
    }

    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            ItemRepresentation::TYPE_WIKI,
            null
        );
    }

    public function visitLink(Docman_Link $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            ItemRepresentation::TYPE_LINK,
            null
        );
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        $item_version    = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_properties = new FilePropertiesRepresentation();
        $file_properties->build($item_version);
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            ItemRepresentation::TYPE_FILE,
            $file_properties
        );
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        $item_version    = $this->docman_version_factory->getCurrentVersionForItem($item);
        $file_properties = new FilePropertiesRepresentation();
        $file_properties->build($item_version);
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            ItemRepresentation::TYPE_EMBEDDED,
            $file_properties
        );
    }

    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $item,
            ItemRepresentation::TYPE_EMPTY,
            null
        );
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return $this->item_representation_builder->buildItemRepresentation($item, null, null);
    }
}
