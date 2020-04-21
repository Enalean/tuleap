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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_OnTop_MappedFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItProvidesTheStatusFieldIfNoMapping(): void
    {
        $tracker  = \Mockery::spy(\Tracker::class);

        $status_field     = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $status_retriever = Mockery::mock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->shouldReceive('getField')->andReturn($status_field);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider(\Mockery::spy(\Cardwall_OnTop_Config::class), $status_retriever);

        $this->assertEquals($status_field, $provider->getField($tracker));
    }

    public function testItProvidesTheMappedFieldIfThereIsAMapping(): void
    {
        $tracker  = Mockery::mock(Tracker::class);

        $mapped_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $status_retriever = \Mockery::spy(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $mapping = Mockery::mock(\Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->shouldReceive('getField')->andReturn($mapped_field);
        $config = Mockery::mock(Cardwall_OnTop_Config::class);
        $config->shouldReceive('getMappingFor')->with($tracker)->andReturn($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);

        $this->assertEquals($mapped_field, $provider->getField($tracker));
    }

    public function testItReturnsNullIfThereIsACustomMappingButNoFieldChoosenYet(): void
    {
        $tracker  = Mockery::mock(Tracker::class);

        $status_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $status_retriever = Mockery::mock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->shouldReceive('getField')->andReturn($status_field);
        $mapping = Mockery::mock(\Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->shouldReceive('getField')->andReturn(null);
        $config = Mockery::mock(Cardwall_OnTop_Config::class);
        $config->shouldReceive('getMappingFor')->with($tracker)->andReturn($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);

        $this->assertEquals(null, $provider->getField($tracker));
    }
}
