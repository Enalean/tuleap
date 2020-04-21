<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class TrackerFormElementExportPermissionsToXmlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPermissionsExport()
    {
        $ugroups = [
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5
        ];

        $field_01 = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $field_01->shouldReceive('getId')->andReturn(10);
        $field_01->shouldReceive('getPermissionsByUgroupId')->andReturn(
            [
                2 => ['FIELDPERM_1'],
                4 => ['FIELDPERM_2'],
            ]
        );
        $field_01->shouldReceive('isUsed')->andReturn(true);

        $xmlMapping['F' . $field_01->getId()] = $field_01->getId();
        $xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><permissions/>');
        $field_01->exportPermissionsToXML($xml, $ugroups, $xmlMapping);

        $this->assertTrue(isset($xml->permission[0]));
        $this->assertTrue(isset($xml->permission[1]));

        $this->assertEquals('field', (string) $xml->permission[0]['scope']);
        $this->assertEquals('UGROUP_2', (string) $xml->permission[0]['ugroup']);
        $this->assertEquals('FIELDPERM_1', (string) $xml->permission[0]['type']);
        $this->assertEquals('F10', (string) $xml->permission[0]['REF']);

        $this->assertEquals('field', (string) $xml->permission[1]['scope']);
        $this->assertEquals('UGROUP_4', (string) $xml->permission[1]['ugroup']);
        $this->assertEquals('FIELDPERM_2', (string) $xml->permission[1]['type']);
        $this->assertEquals('F10', (string) $xml->permission[1]['REF']);
    }
}
