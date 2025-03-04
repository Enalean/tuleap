<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\XML;

use SimpleXMLElement;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLCardwallMappingTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportMappingValue(): void
    {
        $mappings_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_mapping      = (new XMLCardwallMapping('T411', 'F789'));
        $xml_mapping_node = $xml_mapping
            ->withMappingValue(new XMLCardwallMappingValue('Vvalue', 'C150'))
            ->export($mappings_xml);

        self::assertSame('mapping', $xml_mapping_node->getName());
        self::assertEquals('T411', $xml_mapping_node['tracker_id']);
        self::assertEquals('F789', $xml_mapping_node['field_id']);

        self::assertCount(1, $xml_mapping_node->values->children());
        $value_node = $xml_mapping_node->values->value[0];
        self::assertEquals('Vvalue', $value_node['value_id']);
        self::assertEquals('C150', $value_node['column_id']);
    }
}
