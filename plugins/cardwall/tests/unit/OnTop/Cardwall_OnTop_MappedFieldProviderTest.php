<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop;

use Cardwall_FieldProviders_SemanticStatusFieldRetriever;
use Cardwall_OnTop_Config;
use Cardwall_OnTop_Config_MappedFieldProvider;
use Cardwall_OnTop_Config_TrackerMapping;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_MappedFieldProviderTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testItProvidesTheStatusFieldIfNoMapping(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->build();
        $status_field     = OpenListFieldBuilder::anOpenListField()->build();
        $status_retriever = $this->createMock(Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->method('getField')->willReturn($status_field);
        $config   = $this->createMock(Cardwall_OnTop_Config::class);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);
        $config->method('getMappingFor');

        self::assertEquals($status_field, $provider->getField($tracker));
    }

    public function testItProvidesTheMappedFieldIfThereIsAMapping(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->build();
        $mapped_field     = OpenListFieldBuilder::anOpenListField()->build();
        $status_retriever = $this->createMock(Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $mapping          = $this->createMock(Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->method('getField')->willReturn($mapped_field);
        $config = $this->createMock(Cardwall_OnTop_Config::class);
        $config->method('getMappingFor')->with($tracker)->willReturn($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);

        self::assertEquals($mapped_field, $provider->getField($tracker));
    }

    public function testItReturnsNullIfThereIsACustomMappingButNoFieldChoosenYet(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->build();
        $status_field     = OpenListFieldBuilder::anOpenListField()->build();
        $status_retriever = $this->createMock(Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->method('getField')->willReturn($status_field);
        $mapping = $this->createMock(Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->method('getField')->willReturn(null);
        $config = $this->createMock(Cardwall_OnTop_Config::class);
        $config->method('getMappingFor')->with($tracker)->willReturn($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);

        self::assertEquals(null, $provider->getField($tracker));
    }
}
