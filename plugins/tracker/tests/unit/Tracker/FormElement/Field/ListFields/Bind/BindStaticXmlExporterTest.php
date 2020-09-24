<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use BaseLanguage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_BindDecorator;
use Tuleap\GlobalLanguageMock;

final class BindStaticXmlExporterTest extends TestCase
{
    use GlobalLanguageMock;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var BindStaticXmlExporter
     */
    private $exporter;
    /**
     * @var array
     */
    private $default_values;

    /**
     * @var \SimpleXMLElement
     */
    private $xml;

    protected function setUp(): void
    {
        $this->xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->default_values = [1 => true];

        $this->exporter = new BindStaticXmlExporter(new \XML_SimpleXMLCDATAFactory());
        $GLOBALS['Language']  = Mockery::spy(BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getText')->andReturn("None");
    }

    public function testItExportBindWithoutNoneValue(): void
    {
        $values = [
            new Tracker_FormElement_Field_List_Bind_StaticValue(1, "Value A", "description", 1, false),
            new Tracker_FormElement_Field_List_Bind_StaticValue(2, "Value B", "description", 1, true),
        ];
        $decorators = [
            new Tracker_FormElement_Field_List_BindDecorator(123, 1, null, null, null, 'inca-silver'),
            new Tracker_FormElement_Field_List_BindDecorator(123, 2, 123, 456, 789, null),
        ];

        $xml_mapping = [];
        $this->exporter->exportToXml($this->xml, $values, $decorators, $this->default_values, $xml_mapping);

        $items_node = $this->xml->items;
        $this->assertNotNull($items_node);

        $value_A = $items_node->item[0];
        $this->assertLabelAttributeIsSame("Value A", $value_A);


        $value_B = $items_node->item[1];
        $this->assertLabelAttributeIsSame("Value B", $value_B);

        $decorators_node = $this->xml->decorators->decorator;
        $this->assertNotNull($decorators_node);

        $decorator_A = $decorators_node[0];
        $this->assertTlpColor("V1", "inca-silver", $decorator_A);

        $decorator_B = $decorators_node[1];
        $this->assertLegacyColor("V2", "123", "456", "789", $decorator_B);
    }

    public function testItExportBindWithTLPNoneValue(): void
    {
        $values = [
            new Tracker_FormElement_Field_List_Bind_StaticValue(\Tracker_FormElement_Field_List::NONE_VALUE, "None", "description", 1, false)
        ];
        $decorators = [new Tracker_FormElement_Field_List_BindDecorator(\Tracker_FormElement_Field_List::NONE_VALUE, 100, null, null, null, 'inca-silver')];

        $xml_mapping = [];
        $this->exporter->exportToXml($this->xml, $values, $decorators, $this->default_values, $xml_mapping);

        $items_node = $this->xml->items;
        $this->assertNotNull($items_node);

        $value_A = $items_node->item[0];
        $this->assertLabelAttributeIsSame("None", $value_A);

        $decorators_node = $this->xml->decorators->decorator;
        $this->assertNotNull($decorators_node);

        $decorator_none = $decorators_node[0];
        $this->assertTlpColor("V100", "inca-silver", $decorator_none);
    }

    public function testItExportBindWithLegacyNoneValue(): void
    {
        $values = [
            new Tracker_FormElement_Field_List_Bind_StaticValue(\Tracker_FormElement_Field_List::NONE_VALUE, "None", "description", 1, false)
        ];
        $decorators = [new Tracker_FormElement_Field_List_BindDecorator(\Tracker_FormElement_Field_List::NONE_VALUE, 100, "123", "456", "789", null)];

        $xml_mapping = [];
        $this->exporter->exportToXml($this->xml, $values, $decorators, $this->default_values, $xml_mapping);

        $items_node = $this->xml->items;
        $this->assertNotNull($items_node);

        $value_A = $items_node->item[0];
        $this->assertLabelAttributeIsSame("None", $value_A);

        $decorators_node = $this->xml->decorators->decorator;
        $this->assertNotNull($decorators_node);

        $decorator_none = $decorators_node[0];
        $this->assertLegacyColor("V100", "123", "456", "789", $decorator_none);
    }

    private function assertLabelAttributeIsSame(string $expected_value, \SimpleXMLElement $value_node): void
    {
        $this->assertNotNull($value_node);
        $value_A_attributes = $value_node->attributes();
        $this->assertEquals($expected_value, (string) $value_A_attributes->label);
    }

    private function assertTlpColor(string $expected_ref, string $expected_color, \SimpleXMLElement $decorator_node): void
    {
        $decorator_A_attributes = $decorator_node->attributes();
        $this->assertEquals($expected_ref, (string) $decorator_A_attributes->REF);
        $this->assertEquals($expected_color, (string) $decorator_A_attributes->tlp_color_name);
    }

    private function assertLegacyColor(string $expected_value, string $expected_r, string $expected_g, string $expected_b, \SimpleXMLElement $decorator_node): void
    {
        $decorator_B_attributes = $decorator_node->attributes();
        $this->assertEquals($expected_value, (string) $decorator_B_attributes->REF);
        $this->assertEquals($expected_r, (string) $decorator_B_attributes->r);
        $this->assertEquals($expected_g, (string) $decorator_B_attributes->g);
        $this->assertEquals($expected_b, (string) $decorator_B_attributes->b);
    }
}
