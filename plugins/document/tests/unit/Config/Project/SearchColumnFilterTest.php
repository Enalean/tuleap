<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Document\Config\Project;

use Docman_MetadataFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollectionBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class SearchColumnFilterTest extends TestCase
{
    private Docman_MetadataFactory&MockObject $metadata_factory;
    private SearchColumnFilter $search_column_filter;

    protected function setUp(): void
    {
        $this->metadata_factory     = $this->createMock(Docman_MetadataFactory::class);
        $this->search_column_filter = new SearchColumnFilter(
            new SearchColumnCollectionBuilder(),
            new class implements IRetrieveColumns {
                public function searchByProjectId(int $project_id): array
                {
                    return [
                        "title",
                        "description",
                        "status",
                        "obsolescence_date",
                        "field_1",
                    ];
                }
            }
        );
    }

    public function testItGetsSelectedColumn(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->metadata_factory
            ->method('getMetadataForGroup')
            ->willReturn([
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_ID_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_DESCRIPTION_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL),
                $this->getHardcodedMetadata(\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL),
                $this->getCustomMetadata("field_1"),

            ]);

        $selected_columns = $this->search_column_filter->getColumns($project, $this->metadata_factory);
        $expected_columns = [
            [
                "name" => "description",
                "label" => "Description",
                "is_selected" => true,
            ],
            [
                "name" => "status",
                "label" => "Status",
                "is_selected" => true,
            ],
            [
                'name' => 'obsolescence_date',
                'label' => 'Obsolescence_date',
                'is_selected' => true,
            ],
            [
                "name" => "location",
                "label" => "Location",
                "is_selected" => false,
            ],
            [
                "name" => "filename",
                "label" => "Filename",
                "is_selected" => false,
            ],
            [
                "name" => "field_1",
                "label" => "Field_1",
                "is_selected" => true,
            ],
        ];

        self::assertEquals($expected_columns, $selected_columns);
    }

    private function getHardcodedMetadata(string $label): \Docman_Metadata
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel($label);
        $metadata->setName(\ucfirst($label));
        $metadata->setSpecial(true);

        return $metadata;
    }

    private function getCustomMetadata(string $label): \Docman_Metadata
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel($label);
        $metadata->setName(\ucfirst($label));
        $metadata->setSpecial(false);

        return $metadata;
    }
}
