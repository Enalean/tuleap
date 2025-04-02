<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Cardwall_Column;
use Cardwall_OnTop_Config;
use Cardwall_OnTop_Config_TrackerMappingFreestyle;
use Cardwall_OnTop_Config_ValueMapping;
use Cardwall_OnTop_ConfigFactory;
use CardwallConfigXmlExport;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use TrackerFactory;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardwallConfigXmlExport_ColumnsTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Cardwall_OnTop_Config&MockObject $cardwall_config;
    private CardwallConfigXmlExport $xml_exporter;
    private SimpleXMLElement $root;

    protected function setUp(): void
    {
        $project    = ProjectTestBuilder::aProject()->withId(140)->build();
        $tracker1   = TrackerTestBuilder::aTracker()->withId(214)->build();
        $this->root = new SimpleXMLElement('<projects/>');

        $this->cardwall_config = $this->createMock(Cardwall_OnTop_Config::class);
        $this->cardwall_config->method('isEnabled')->willReturn(true);

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackersByGroupId')->with(140)->willReturn([214 => $tracker1]);

        $config_factory = $this->createMock(Cardwall_OnTop_ConfigFactory::class);
        $config_factory->method('getOnTopConfig')->with($tracker1)->willReturn($this->cardwall_config);

        $xml_validator = $this->createMock(XML_RNGValidator::class);
        $xml_validator->method('validate');

        $this->xml_exporter = new CardwallConfigXmlExport($project, $tracker_factory, $config_factory, $xml_validator);
    }

    public function testItDumpsNoColumnsWhenNoColumnsDefined(): void
    {
        $this->cardwall_config->method('getDashboardColumns')->willReturn(new ColumnCollection([]));
        $this->cardwall_config->method('getMappings')->willReturn([]);

        $this->xml_exporter->export($this->root);
        self::assertCount(0, $this->root->cardwall->trackers->tracker->children());
    }

    public function testItDumpsColumnsAsDefined(): void
    {
        $this->cardwall_config->method('getDashboardColumns')->willReturn(new ColumnCollection([
            new Cardwall_Column(112, 'Todo', 'red'),
            new Cardwall_Column(113, 'On going', 'fiesta-red'),
            new Cardwall_Column(113, 'On going', 'rgb(255,255,255)'),
        ]));

        $this->cardwall_config->method('getMappings')->willReturn([]);

        $this->xml_exporter->export($this->root);
        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;

        self::assertCount(3, $column_xml);
    }

    public function testItDumpsColumnsAsDefinedWithMappings(): void
    {
        $this->cardwall_config->method('getDashboardColumns')->willReturn(new ColumnCollection([
            new Cardwall_Column(112, 'Todo', 'red'),
            new Cardwall_Column(113, 'On going', 'fiesta-red'),
            new Cardwall_Column(113, 'On going', 'rgb(255,255,255)'),
        ]));

        $tracker = TrackerTestBuilder::aTracker()->withId(200)->build();
        $field   = ListFieldBuilder::aListField(201)->build();

        $value_mapping = $this->createMock(Cardwall_OnTop_Config_ValueMapping::class);
        $value_mapping->method('getXMLValueId')->willReturn('0195f58f-8e6f-719c-8bb7-e36ae3e11872');
        $value_mapping->method('getColumnId')->willReturn(4);

        $mapping = $this->createMock(Cardwall_OnTop_Config_TrackerMappingFreestyle::class);
        $mapping->method('getTracker')->willReturn($tracker);
        $mapping->method('getField')->willReturn($field);
        $mapping->method('getValueMappings')->willReturn([$value_mapping]);
        $mapping->method('isCustom')->willReturn(true);

        $this->cardwall_config->method('getMappings')->willReturn([$mapping]);

        $this->xml_exporter->export($this->root);

        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;
        self::assertCount(3, $column_xml);

        $mapping_xml = $this->root->cardwall->trackers->tracker->mappings->mapping;
        self::assertCount(1, $mapping_xml);
        self::assertEquals('T200', $mapping_xml['tracker_id']);
        self::assertEquals('F201', $mapping_xml['field_id']);

        $mapping_values_xml = $this->root->cardwall->trackers->tracker->mappings->mapping->values->value;
        self::assertCount(1, $mapping_values_xml);
        self::assertEquals('0195f58f-8e6f-719c-8bb7-e36ae3e11872', $mapping_values_xml['value_id']);
        self::assertEquals('C4', $mapping_values_xml['column_id']);
    }
}
