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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindDecorator;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\NoneStaticValueBuilder;
use XML_SimpleXMLCDATAFactory;

#[DisableReturnValueGenerationForTestDoubles]
final class BindStaticXmlExporterTest extends TestCase
{
    use GlobalLanguageMock;

    private BindStaticXmlExporter $exporter;
    private array $default_values;
    private SimpleXMLElement $xml;

    protected function setUp(): void
    {
        $this->xml            = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->default_values = [1 => true];

        $this->exporter = new BindStaticXmlExporter(new XML_SimpleXMLCDATAFactory());
        $GLOBALS['Language']->method('getText')->willReturn('None');
    }

    public function testItExportBindWithoutNoneValue(): void
    {
        $value_a    = ListStaticValueBuilder::aStaticValue('Value A')->withId(1)->build();
        $value_b    = ListStaticValueBuilder::aStaticValue('Value B')->withId(2)->build();
        $values     = [
            $value_a,
            $value_b,
        ];
        $decorators = [
            new Tracker_FormElement_Field_List_BindDecorator(123, 1, null, null, null, 'inca-silver'),
            new Tracker_FormElement_Field_List_BindDecorator(123, 2, 123, 456, 789, null),
        ];

        $xml_mapping = [];
        $this->exporter->exportStaticBindToXml($this->xml, $values, $decorators, $this->default_values, $xml_mapping);

        $items_node = $this->xml->items;
        self::assertNotNull($items_node);

        $value_A = $items_node->item[0];
        self::assertLabelAttributeIsSame('Value A', $value_A);


        $value_B = $items_node->item[1];
        self::assertLabelAttributeIsSame('Value B', $value_B);

        $decorators_node = $this->xml->decorators->decorator;
        self::assertNotNull($decorators_node);

        $decorator_A = $decorators_node[0];
        self::assertTlpColor($value_a->getUuid(), 'inca-silver', $decorator_A);

        $decorator_B = $decorators_node[1];
        self::assertLegacyColor($value_b->getUuid(), '123', '456', '789', $decorator_B);
    }

    public function testItExportBindWithTLPNoneValue(): void
    {
        $none_value = NoneStaticValueBuilder::build();
        $values     = [
            $none_value,
        ];
        $decorators = [new Tracker_FormElement_Field_List_BindDecorator(Tracker_FormElement_Field_List::NONE_VALUE, 100, null, null, null, 'inca-silver')];

        $xml_mapping = [];
        $this->exporter->exportStaticBindToXml($this->xml, $values, $decorators, $this->default_values, $xml_mapping);

        $items_node = $this->xml->items;
        self::assertNotNull($items_node);

        $value_A = $items_node->item[0];
        self::assertLabelAttributeIsSame('None', $value_A);

        $decorators_node = $this->xml->decorators->decorator;
        self::assertNotNull($decorators_node);

        $decorator_none = $decorators_node[0];
        self::assertTlpColor($none_value->getUuid(), 'inca-silver', $decorator_none);
    }

    public function testItExportBindWithLegacyNoneValue(): void
    {
        $none_value = NoneStaticValueBuilder::build();
        $values     = [
            $none_value,
        ];
        $decorators = [new Tracker_FormElement_Field_List_BindDecorator(Tracker_FormElement_Field_List::NONE_VALUE, 100, '123', '456', '789', null)];

        $xml_mapping = [];
        $this->exporter->exportStaticBindToXml($this->xml, $values, $decorators, $this->default_values, $xml_mapping);

        $items_node = $this->xml->items;
        self::assertNotNull($items_node);

        $value_A = $items_node->item[0];
        self::assertLabelAttributeIsSame('None', $value_A);

        $decorators_node = $this->xml->decorators->decorator;
        self::assertNotNull($decorators_node);

        $decorator_none = $decorators_node[0];
        self::assertLegacyColor($none_value->getUuid(), '123', '456', '789', $decorator_none);
    }

    private function assertLabelAttributeIsSame(string $expected_value, SimpleXMLElement $value_node): void
    {
        self::assertNotNull($value_node);
        $value_A_attributes = $value_node->attributes();
        self::assertEquals($expected_value, (string) $value_A_attributes->label);
    }

    private function assertTlpColor(string $expected_ref, string $expected_color, SimpleXMLElement $decorator_node): void
    {
        $decorator_A_attributes = $decorator_node->attributes();
        self::assertEquals($expected_ref, (string) $decorator_A_attributes->REF);
        self::assertEquals($expected_color, (string) $decorator_A_attributes->tlp_color_name);
    }

    private function assertLegacyColor(string $expected_value, string $expected_r, string $expected_g, string $expected_b, SimpleXMLElement $decorator_node): void
    {
        $decorator_B_attributes = $decorator_node->attributes();
        self::assertEquals($expected_value, (string) $decorator_B_attributes->REF);
        self::assertEquals($expected_r, (string) $decorator_B_attributes->r);
        self::assertEquals($expected_g, (string) $decorator_B_attributes->g);
        self::assertEquals($expected_b, (string) $decorator_B_attributes->b);
    }
}
