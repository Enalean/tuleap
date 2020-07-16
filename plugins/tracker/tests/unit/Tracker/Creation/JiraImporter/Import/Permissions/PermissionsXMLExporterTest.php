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
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Permissions\PermissionsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class PermissionsXMLExporterTest extends TestCase
{
    public function testItExportsDefaultPermissionsForFieldsInMapping(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                null
            )
        );

        $exporter = new PermissionsXMLExporter();
        $exporter->exportFieldsPermissions(
            $tracker_node,
            $mapping_collection
        );

        $permissions_node = $tracker_node->permissions;
        $this->assertNotNull($permissions_node);
        $this->assertCount(3, $permissions_node->children());

        $read_permission_node = $permissions_node->permission[0];
        $this->assertSame("field", (string) $read_permission_node['scope']);
        $this->assertSame("Fsummary", (string) $read_permission_node['REF']);
        $this->assertSame("UGROUP_ANONYMOUS", (string) $read_permission_node['ugroup']);
        $this->assertSame("PLUGIN_TRACKER_FIELD_READ", (string) $read_permission_node['type']);

        $submit_permission_node = $permissions_node->permission[1];
        $this->assertSame("field", (string) $submit_permission_node['scope']);
        $this->assertSame("Fsummary", (string) $submit_permission_node['REF']);
        $this->assertSame("UGROUP_REGISTERED", (string) $submit_permission_node['ugroup']);
        $this->assertSame("PLUGIN_TRACKER_FIELD_SUBMIT", (string) $submit_permission_node['type']);

        $update_permission_node = $permissions_node->permission[2];
        $this->assertSame("field", (string) $update_permission_node['scope']);
        $this->assertSame("Fsummary", (string) $update_permission_node['REF']);
        $this->assertSame("UGROUP_PROJECT_MEMBERS", (string) $update_permission_node['ugroup']);
        $this->assertSame("PLUGIN_TRACKER_FIELD_UPDATE", (string) $update_permission_node['type']);
    }

    public function testItExportsOnlyReadPermissionsForArtifactIdField(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'artifact_id',
                'Fartifact_id',
                'Artifact Id',
                Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
                null
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

        $read_permission_node = $permissions_node->permission[0];
        $this->assertSame("field", (string) $read_permission_node['scope']);
        $this->assertSame("Fartifact_id", (string) $read_permission_node['REF']);
        $this->assertSame("UGROUP_ANONYMOUS", (string) $read_permission_node['ugroup']);
        $this->assertSame("PLUGIN_TRACKER_FIELD_READ", (string) $read_permission_node['type']);
    }

    public function testItExportsOnlyReadPermissionsForOldJiraLinkField(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'jira_issue_url',
                'Fjira_issue_url',
                'Link to original issue',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                null
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

        $read_permission_node = $permissions_node->permission[0];
        $this->assertSame("field", (string) $read_permission_node['scope']);
        $this->assertSame("Fjira_issue_url", (string) $read_permission_node['REF']);
        $this->assertSame("UGROUP_ANONYMOUS", (string) $read_permission_node['ugroup']);
        $this->assertSame("PLUGIN_TRACKER_FIELD_READ", (string) $read_permission_node['type']);
    }
}
