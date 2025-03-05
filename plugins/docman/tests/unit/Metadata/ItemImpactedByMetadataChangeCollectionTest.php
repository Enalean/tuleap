<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use Docman_Metadata;
use Tuleap\Docman\REST\v1\Metadata\MetadataToUpdate;
use Tuleap\Docman\REST\v1\Metadata\PUTCustomMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataFolderRepresentation;
use Tuleap\Docman\REST\v1\Metadata\PUTRecursiveStatusRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemImpactedByMetadataChangeCollectionTest extends TestCase
{
    public function testItBuildCollectionForLegacy(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1', 'field_2', 'status'], ['field_1' => 'value', 'field_2' => 'other value']);

        self::assertEquals(['field_1', 'field_2', 'status'], $collection->getFieldsToUpdate());
        self::assertEquals(['field_1' => 'value', 'field_2' => 'other value', 'status' => ''], $collection->getValuesToExtractCrossReferences());
    }

    public function testItBuildCollectionForRest(): void
    {
        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS;

        $custom_metadata             = new PUTCustomMetadataRepresentation();
        $custom_metadata->short_name = 'field_1';
        $custom_metadata->value      = 'some_value';
        $custom_metadata->recursion  = PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS;

        $other_custom_metadata             = new PUTCustomMetadataRepresentation();
        $other_custom_metadata->short_name = 'field_2';
        $other_custom_metadata->value      = '';
        $other_custom_metadata->recursion  = 'none';

        $metadata = new Docman_Metadata();
        $metadata->initFromRow(['label' => 'field_1']);
        $metadata_to_update = [
            MetadataToUpdate::buildMetadataRepresentation(
                $metadata,
                $custom_metadata->value,
                $custom_metadata->recursion
            ),
            MetadataToUpdate::buildMetadataRepresentation(
                new Docman_Metadata(),
                $other_custom_metadata->value,
                $other_custom_metadata->recursion
            ),
        ];
        $collection         = ItemImpactedByMetadataChangeCollection::buildFromRest(
            $representation,
            $metadata_to_update,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );

        self::assertEquals(['status', 'field_1'], $collection->getFieldsToUpdate());
        self::assertEquals(['status' => 'draft', 'field_1' => 'some_value'], $collection->getValuesToExtractCrossReferences());
    }
}
