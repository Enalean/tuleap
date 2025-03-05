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

namespace Tuleap\Document\Tree\Search;

use Tuleap\Docman\REST\v1\Search\SearchColumnCollectionBuilder;
use Tuleap\Document\Config\Project\IRetrieveColumns;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListOfSearchColumnDefinitionPresenterBuilderTest extends TestCase
{
    public function testItBuildsPresenters(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

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
            ]);

        $columns = (new ListOfSearchColumnDefinitionPresenterBuilder(
            new SearchColumnCollectionBuilder(),
            new class implements IRetrieveColumns {
                public function searchByProjectId(int $project_id): array
                {
                    return [
                        'title',
                        'id',
                        'description',
                        'owner',
                        'update_date',
                        'create_date',
                        'status',
                        'obsolescence_date',
                        'location',
                    ];
                }
            }
        ))
            ->getColumns($project, $metadata_factory);

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
            ],
            array_map(
                static fn(SearchColumnDefinitionPresenter $column): string => $column->name,
                $columns,
            ),
        );
    }

    public function testItReturnsOnlyColumnsSelectedByConfigurationIncludingIdAndTitle(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

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
            ]);

        $columns = (new ListOfSearchColumnDefinitionPresenterBuilder(
            new SearchColumnCollectionBuilder(),
            new class implements IRetrieveColumns {
                public function searchByProjectId(int $project_id): array
                {
                    return [
                        'update_date',
                        'create_date',
                    ];
                }
            }
        ))
            ->getColumns($project, $metadata_factory);

        self::assertEquals(
            [
                'id',
                'title',
                'update_date',
                'create_date',
            ],
            array_map(
                static fn(SearchColumnDefinitionPresenter $column): string => $column->name,
                $columns,
            ),
        );
    }

    public function testItReturnsAllCurrentPropertiesAsColumnsIfNoConfigurationHasBeenSetExplicitely(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

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
            ]);

        $columns = (new ListOfSearchColumnDefinitionPresenterBuilder(
            new SearchColumnCollectionBuilder(),
            new class implements IRetrieveColumns {
                public function searchByProjectId(int $project_id): array
                {
                    return [];
                }
            }
        ))
            ->getColumns($project, $metadata_factory);

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
            ],
            array_map(
                static fn(SearchColumnDefinitionPresenter $column): string => $column->name,
                $columns,
            ),
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
}
