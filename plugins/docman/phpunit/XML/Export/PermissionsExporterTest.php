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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PermissionsExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDoesNotExportIfNoPermissions(): void
    {
        $dao = Mockery::mock(PermissionsExporterDao::class);
        $dao->shouldReceive('searchPermissions')->with(42)->andReturn([]);

        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $item = Mockery::mock(\Docman_Item::class)->shouldReceive(['getId' => 42])->getMock();

        $exporter = new PermissionsExporter($dao, []);
        $exporter->exportPermissions($xml, $item);

        $this->assertEmpty($xml->permissions);
    }

    public function testItExportPermissions(): void
    {
        $dao = Mockery::mock(PermissionsExporterDao::class);
        $dao->shouldReceive('searchPermissions')
            ->with(42)
            ->andReturn(
                [
                    [
                        'ugroup_id'       => 2,
                        'permission_type' => 'PLUGIN_DOCMAN_READ'
                    ]
                ]
            );

        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $item = Mockery::mock(\Docman_Item::class)->shouldReceive(['getId' => 42])->getMock();

        $exporter = new PermissionsExporter($dao, ['UGROUP_REGISTERED' => 2]);
        $exporter->exportPermissions($xml, $item);

        $this->assertEquals('PLUGIN_DOCMAN_READ', (string) $xml->permissions->permission[0]['type']);
        $this->assertEquals('UGROUP_REGISTERED', (string) $xml->permissions->permission[0]['ugroup']);
    }

    public function testItIgnoresUnknownUgroup(): void
    {
        $dao = Mockery::mock(PermissionsExporterDao::class);
        $dao->shouldReceive('searchPermissions')
            ->with(42)
            ->andReturn(
                [
                    [
                        'ugroup_id'       => 102,
                        'permission_type' => 'PLUGIN_DOCMAN_READ'
                    ]
                ]
            );

        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $item = Mockery::mock(\Docman_Item::class)->shouldReceive(['getId' => 42])->getMock();

        $exporter = new PermissionsExporter($dao, ['UGROUP_REGISTERED' => 2]);
        $exporter->exportPermissions($xml, $item);

        $this->assertEmpty($xml->permissions->permission);
    }
}
