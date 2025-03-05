<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\XML\Export;

use Docman_Item;
use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsExporterTest extends TestCase
{
    public function testItDoesNotExportIfNoPermissions(): void
    {
        $dao = $this->createMock(PermissionsExporterDao::class);
        $dao->method('searchPermissions')->with(42)->willReturn([]);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $item = new Docman_Item(['item_id' => 42]);

        $exporter = new PermissionsExporter($dao, []);
        $exporter->exportPermissions($xml, $item);

        self::assertEmpty($xml->permissions);
    }

    public function testItExportPermissions(): void
    {
        $dao = $this->createMock(PermissionsExporterDao::class);
        $dao->method('searchPermissions')->with(42)->willReturn([
            [
                'ugroup_id'       => 2,
                'permission_type' => 'PLUGIN_DOCMAN_READ',
            ],
        ]);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $item = new Docman_Item(['item_id' => 42]);

        $exporter = new PermissionsExporter($dao, ['UGROUP_REGISTERED' => 2]);
        $exporter->exportPermissions($xml, $item);

        self::assertEquals('PLUGIN_DOCMAN_READ', (string) $xml->permissions->permission[0]['type']);
        self::assertEquals('UGROUP_REGISTERED', (string) $xml->permissions->permission[0]['ugroup']);
    }

    public function testItIgnoresUnknownUgroup(): void
    {
        $dao = $this->createMock(PermissionsExporterDao::class);
        $dao->method('searchPermissions')->with(42)->willReturn([
            [
                'ugroup_id'       => 102,
                'permission_type' => 'PLUGIN_DOCMAN_READ',
            ],
        ]);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $item = new Docman_Item(['item_id' => 42]);

        $exporter = new PermissionsExporter($dao, ['UGROUP_REGISTERED' => 2]);
        $exporter->exportPermissions($xml, $item);

        self::assertEmpty($xml->permissions->permission);
    }
}
