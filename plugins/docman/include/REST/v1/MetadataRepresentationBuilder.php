<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_MetadataListOfValuesElement;

class MetadataRepresentationBuilder
{
    /**
     * @var \Docman_MetadataFactory
     */
    private $factory;

    public function __construct(\Docman_MetadataFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return MetadataRepresentation[]
     * @throws UnknownMetadataException
     */
    public function build(\Docman_Item $item) : array
    {
        $this->factory->appendItemMetadataListWithoutBasicProperties($item);

        $metadata_representations = [];

        $item_metadata = $item->getMetadata();

        foreach ($item_metadata as $metadata) {
            $value      = $metadata->getValue();
            $list_value = null;
            $date_value = null;
            if (is_object($value)) {
                /**
                 * @var Docman_MetadataListOfValuesElement $metadata_value
                 */
                foreach ($value as $metadata_value) {
                    $list_value[] = new MetadataListValueRepresentation(
                        (int)$metadata_value->getId(),
                        $metadata_value->getName()
                    );
                }

                $value = null;
            }

            $metadata_representations[] = new MetadataRepresentation(
                $metadata->getName(),
                $this->getMetadataType((int)$metadata->getType()),
                $metadata->isMultipleValuesAllowed(),
                (string) $value,
                $list_value,
                $metadata->isRequired()
            );
        }

        return $metadata_representations;
    }

    /**
     * @param int $type
     *
     * @return string
     * @throws UnknownMetadataException
     */
    private function getMetadataType(int $type): string
    {
        switch ($type) {
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                return 'text';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                return 'string';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                return 'date';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                return 'list';
                break;
            default:
                throw new UnknownMetadataException("Metadata type: " . $type . " unknown");
                break;
        }
    }
}
