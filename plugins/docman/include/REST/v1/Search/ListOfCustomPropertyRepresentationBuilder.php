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

use Tuleap\REST\JsonCast;

final class ListOfCustomPropertyRepresentationBuilder implements CustomPropertyRepresentation
{
    /**
     * @return array<string, CustomPropertyRepresentation>
     */
    public function getCustomProperties(\Docman_Item $item, SearchColumnCollection $wanted_custom_properties): array
    {
        $all_retrieved_metadata = $item->getMetadataIterator();
        $custom_properties      = [];
        foreach ($wanted_custom_properties->getColumns() as $wanted_column) {
            if (! isset($all_retrieved_metadata[$wanted_column->getName()])) {
                continue;
            }

            $metadata = $all_retrieved_metadata[$wanted_column->getName()];
            assert($metadata instanceof \Docman_Metadata);

            $type = (int) $metadata->getType();

            switch ($type) {
                case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    $iterator = $metadata->getValue();
                    if ($iterator instanceof \ArrayIterator) {
                        $custom_properties[$wanted_column->getName()] = new CustomPropertyListRepresentation(
                            'list',
                            array_map(
                                static fn(\Docman_MetadataListOfValuesElement $value,
                                ): string => $value->getMetadataValue(),
                                \iterator_to_array($iterator),
                            ),
                        );
                    }
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    $custom_properties[$wanted_column->getName()] = new CustomPropertyDateRepresentation(
                        'date',
                        JsonCast::toDate($metadata->getValue() > 0 ? $metadata->getValue() : null),
                    );
                    break;
                default:
                    $custom_properties[$wanted_column->getName()] = new CustomPropertyStringRepresentation(
                        'string',
                        (string) $metadata->getValue(),
                    );
            }
        }

        return $custom_properties;
    }
}
