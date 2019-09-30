<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class MappedFieldRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MappedFieldRetriever
     */
    private $mapped_field_retriever;
    /**
     * @var \Cardwall_FieldProviders_SemanticStatusFieldRetriever|M\LegacyMockInterface|M\MockInterface
     */
    private $status_retriever;

    protected function setUp(): void
    {
        $this->status_retriever       = M::mock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->mapped_field_retriever = new MappedFieldRetriever($this->status_retriever);
    }

    public function testReturnsFieldMappedInConfig(): void
    {
        $tracker      = M::mock(\Tracker::class);
        $mapped_field = M::mock(\Tracker_FormElement_Field_Selectbox::class);
        $mapping      = M::mock(\Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->shouldReceive('getField')
            ->once()
            ->andReturn($mapped_field);
        $config = M::mock(\Cardwall_OnTop_Config::class);
        $config->shouldReceive('getMappingFor')
            ->with($tracker)
            ->once()
            ->andReturn($mapping);

        $result = $this->mapped_field_retriever->getField($config, $tracker);

        $this->assertSame($mapped_field, $result);
    }

    public function testReturnsNullWhenMappedFieldIsNotList(): void
    {
        $tracker      = M::mock(\Tracker::class);
        $mapped_field = M::mock(\Tracker_FormElement_Field_Integer::class);
        $mapping      = M::mock(\Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->shouldReceive('getField')
            ->once()
            ->andReturn($mapped_field);
        $config = M::mock(\Cardwall_OnTop_Config::class);
        $config->shouldReceive('getMappingFor')
            ->with($tracker)
            ->once()
            ->andReturn($mapping);

        $result = $this->mapped_field_retriever->getField($config, $tracker);

        $this->assertNull($result);
    }

    public function testReturnsStatusSemanticWhenNoMapping(): void
    {
        $tracker      = M::mock(\Tracker::class);
        $mapped_field = M::mock(\Tracker_FormElement_Field_Selectbox::class);
        $config       = M::mock(\Cardwall_OnTop_Config::class);
        $config->shouldReceive('getMappingFor')
            ->with($tracker)
            ->once()
            ->andReturnNull();
        $this->status_retriever->shouldReceive('getField')
            ->with($tracker)
            ->once()
            ->andReturn($mapped_field);

        $result = $this->mapped_field_retriever->getField($config, $tracker);

        $this->assertSame($mapped_field, $result);
    }
}
