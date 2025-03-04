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

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchColumnCollectionBuilderTest extends TestCase
{
    public function testGetColumnsAlwaysReturnIdAndTitleAtFirstPosition(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory
            ->method('getMetadataForGroup')
            ->willReturn([]);

        $collection = (new SearchColumnCollectionBuilder())->getCollection($metadata_factory);

        self::assertEquals('id', $collection->getColumnNames()[0]);
        self::assertEquals('title', $collection->getColumnNames()[1]);
    }

    public function testItReturnsIdThenHardcodedPropertiesThenLocationThenCustomProperties(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory
            ->method('getMetadataForGroup')
            ->willReturn([
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_DESCRIPTION_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_OWNER_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_UPDATE_DATE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_CREATE_DATE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL),
                $this->getCustomMetadata('field_1', 'Custom prop 1', true),
                $this->getCustomMetadata('field_2', 'Custom prop 2', false),
            ]);

        $collection = (new SearchColumnCollectionBuilder())->getCollection($metadata_factory);

        self::assertEquals(
            [
                'id',
                'title',
                'description',
                'owner',
                'update_date',
                'create_date',
                'status',
                'obsolescence_date',
                'location',
                'filename',
                'field_1',
                'field_2',
            ],
            $collection->getColumnNames(),
        );
    }

    public function testCustomColumnsAreAlphabeticallySorted(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory
            ->method('getMetadataForGroup')
            ->willReturn([
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_DESCRIPTION_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_OWNER_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_UPDATE_DATE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_CREATE_DATE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL),
                $this->getCustomMetadata('field_1', 'ZZZ', true),
                $this->getCustomMetadata('field_2', 'AAA', false),
            ]);

        $collection = (new SearchColumnCollectionBuilder())->getCollection($metadata_factory);

        self::assertEquals(
            [
                'id',
                'title',
                'description',
                'owner',
                'update_date',
                'create_date',
                'status',
                'obsolescence_date',
                'location',
                'filename',
                'field_2',
                'field_1',
            ],
            $collection->getColumnNames(),
        );
    }

    private function getHardcodedMetadata(string $label): \Docman_Metadata
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel($label);
        $metadata->setName(\ucfirst($label));
        $metadata->setSpecial(true);

        return $metadata;
    }

    private function getCustomMetadata(string $label, string $name, bool $is_multiple_value_allowed): \Docman_Metadata
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel($label);
        $metadata->setName($name);
        $metadata->setSpecial(false);
        $metadata->setIsMultipleValuesAllowed($is_multiple_value_allowed);

        return $metadata;
    }
}
