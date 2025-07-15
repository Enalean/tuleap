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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFormElementExportPermissionsToXmlTest extends TestCase
{
    public function testPermissionsExport(): void
    {
        $ugroups = [
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        ];

        $field_01 = $this->createPartialMock(StringField::class, [
            'getId', 'getPermissionsByUgroupId', 'isUsed',
        ]);

        $field_01->method('getId')->willReturn(10);
        $field_01->method('getPermissionsByUgroupId')->willReturn([
            2 => ['FIELDPERM_1'],
            4 => ['FIELDPERM_2'],
        ]);
        $field_01->method('isUsed')->willReturn(true);

        $xmlMapping['F' . $field_01->getId()] = $field_01->getId();
        $xml                                  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><permissions/>');
        $field_01->exportPermissionsToXML($xml, $ugroups, $xmlMapping);

        self::assertTrue(isset($xml->permission[0]));
        self::assertTrue(isset($xml->permission[1]));

        self::assertEquals('field', (string) $xml->permission[0]['scope']);
        self::assertEquals('UGROUP_2', (string) $xml->permission[0]['ugroup']);
        self::assertEquals('FIELDPERM_1', (string) $xml->permission[0]['type']);
        self::assertEquals('F10', (string) $xml->permission[0]['REF']);

        self::assertEquals('field', (string) $xml->permission[1]['scope']);
        self::assertEquals('UGROUP_4', (string) $xml->permission[1]['ugroup']);
        self::assertEquals('FIELDPERM_2', (string) $xml->permission[1]['type']);
        self::assertEquals('F10', (string) $xml->permission[1]['REF']);
    }
}
