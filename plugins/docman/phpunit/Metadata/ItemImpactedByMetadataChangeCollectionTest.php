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

declare(strict_types = 1);

namespace Tuleap\Docman\Metadata;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Metadata\MetadataToUpdate;
use Tuleap\Docman\REST\v1\Metadata\PUTCustomMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataFolderRepresentation;
use Tuleap\Docman\REST\v1\Metadata\PUTRecursiveStatusRepresentation;

class ItemImpactedByMetadataChangeCollectionTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildCollectionForLegacy(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1', 'field_2', 'status'], ['field_1' => 'value', 'field_2' => 'other value']);

        $this->assertEquals($collection->getFieldsToUpdate(), ['field_1', 'field_2', 'status']);
        $this->assertEquals($collection->getValuesToExtractCrossReferences(), ['field_1' => 'value', 'field_2' => 'other value', 'status' => '']);
    }

    public function testItBuildCollectionForRest(): void
    {
        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS;

        $custom_metadata    = new PUTCustomMetadataRepresentation();
        $custom_metadata->short_name = "field_1";
        $custom_metadata->value = "some_value";
        $custom_metadata->recursion = PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS;

        $other_custom_metadata    = new PUTCustomMetadataRepresentation();
        $other_custom_metadata->short_name = "field_2";
        $other_custom_metadata->value = "";
        $other_custom_metadata->recursion = "none";

        $metadata           = Mockery::mock(\Docman_Metadata::class);
        $metadata->shouldReceive('getLabel')->andReturn('field_1');
        $metadata_to_update = [
            MetadataToUpdate::buildMetadataRepresentation(
                $metadata,
                $custom_metadata->value,
                $custom_metadata->recursion
            ),
            MetadataToUpdate::buildMetadataRepresentation(
                Mockery::mock(\Docman_Metadata::class),
                $other_custom_metadata->value,
                $other_custom_metadata->recursion
            ),
        ];
        $collection         = ItemImpactedByMetadataChangeCollection::buildFromRest(
            $representation,
            $metadata_to_update,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );

        $this->assertEquals($collection->getFieldsToUpdate(), ['status', 'field_1']);
        $this->assertEquals($collection->getValuesToExtractCrossReferences(), ['status' => 'draft', 'field_1' => 'some_value']);
    }
}
