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

use Tuleap\Test\PHPUnit\TestCase;

class ListOfSearchColumnDefinitionPresenterBuilderTest extends TestCase
{
    public function testGetColumns(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);

        $status_metadata = new \Docman_Metadata();
        $status_metadata->setLabel(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL);
        $status_metadata->setName('Status');
        $status_metadata->setUseIt(true);

        $obsolescence_metadata = new \Docman_Metadata();
        $obsolescence_metadata->setLabel(\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL);
        $obsolescence_metadata->setName('Obsolescence');
        $obsolescence_metadata->setUseIt(true);

        $metadata_factory->method('getHardCodedMetadataFromLabel')
            ->willReturnMap([
                [\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL, null, $status_metadata],
                [\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL, null, $obsolescence_metadata],
            ]);
        $metadata_factory->method('appendHardCodedMetadataParams');

        $columns = (new ListOfSearchColumnDefinitionPresenterBuilder())->getColumns($metadata_factory);

        self::assertEquals(
            ["id", "title", "description", "owner", "update_date", "create_date", "location", "status", "obsolescence_date"],
            array_map(
                static fn(SearchColumnDefinitionPresenter $column): string => $column->name,
                $columns,
            ),
        );
    }

    public function testGetColumnsWithoutStatus(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);

        $status_metadata = new \Docman_Metadata();
        $status_metadata->setLabel(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL);
        $status_metadata->setName('Status');
        $status_metadata->setUseIt(false);

        $obsolescence_metadata = new \Docman_Metadata();
        $obsolescence_metadata->setLabel(\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL);
        $obsolescence_metadata->setName('Obsolescence');
        $obsolescence_metadata->setUseIt(true);

        $metadata_factory->method('getHardCodedMetadataFromLabel')
            ->willReturnMap([
                [\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL, null, $status_metadata],
                [\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL, null, $obsolescence_metadata],
            ]);
        $metadata_factory->method('appendHardCodedMetadataParams');

        $columns = (new ListOfSearchColumnDefinitionPresenterBuilder())->getColumns($metadata_factory);

        self::assertEquals(
            ["id", "title", "description", "owner", "update_date", "create_date", "location", "obsolescence_date"],
            array_map(
                static fn(SearchColumnDefinitionPresenter $column): string => $column->name,
                $columns,
            ),
        );
    }

    public function testGetColumnsWithoutObsolescence(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);

        $status_metadata = new \Docman_Metadata();
        $status_metadata->setLabel(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL);
        $status_metadata->setName('Status');
        $status_metadata->setUseIt(true);

        $obsolescence_metadata = new \Docman_Metadata();
        $obsolescence_metadata->setLabel(\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL);
        $obsolescence_metadata->setName('Obsolescence');
        $obsolescence_metadata->setUseIt(false);

        $metadata_factory->method('getHardCodedMetadataFromLabel')
            ->willReturnMap([
                [\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL, null, $status_metadata],
                [\Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL, null, $obsolescence_metadata],
            ]);
        $metadata_factory->method('appendHardCodedMetadataParams');

        $columns = (new ListOfSearchColumnDefinitionPresenterBuilder())->getColumns($metadata_factory);

        self::assertEquals(
            ["id", "title", "description", "owner", "update_date", "create_date", "location", "status"],
            array_map(
                static fn(SearchColumnDefinitionPresenter $column): string => $column->name,
                $columns,
            ),
        );
    }
}
