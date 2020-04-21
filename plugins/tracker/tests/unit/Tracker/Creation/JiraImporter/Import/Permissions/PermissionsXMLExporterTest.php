<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tracker\Creation\JiraImporter\Import\Permissions;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\Permissions\PermissionsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class PermissionsXMLExporterTest extends TestCase
{
    public function testItExportsReadPermissionsForFieldsInMapping(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary'
            )
        );

        $exporter = new PermissionsXMLExporter();
        $exporter->exportFieldsPermissions(
            $tracker_node,
            $mapping_collection
        );

        $permissions_node = $tracker_node->permissions;
        $this->assertNotNull($permissions_node);
        $this->assertCount(1, $permissions_node->children());

        $permission_node = $permissions_node->permission;
        $this->assertSame("field", (string) $permission_node['scope']);
        $this->assertSame("Fsummary", (string) $permission_node['REF']);
        $this->assertSame("UGROUP_PROJECT_MEMBERS", (string) $permission_node['ugroup']);
        $this->assertSame("PLUGIN_TRACKER_FIELD_READ", (string) $permission_node['type']);
    }
}
