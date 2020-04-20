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

namespace Tracker\Creation\JiraImporter\Import\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use UserManager;
use XML_SimpleXMLCDATAFactory;

class ArtifactsXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsArtifacts(): void
    {
        $wrapper      = Mockery::mock(ClientWrapper::class);
        $user_manager = Mockery::mock(UserManager::class);

        $exporter = new ArtifactsXMLExporter(
            $wrapper,
            new XML_SimpleXMLCDATAFactory(),
            $user_manager
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('user01');

        $user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary'
            )
        );
        $jira_project_id = 'project';
        $jira_base_url   = 'URLinstance';

        $wrapper->shouldReceive('getUrl')->andReturn([
            'startAt' => 0,
            'maxResults' => 50,
            'total' => 7,
            'issues' => [
                [
                    'id' => '10042',
                    'self' => 'https://jira_instance/rest/api/latest/issue/10042',
                    'key' => 'key01',
                    'fields' => [
                        'summary' => 'summary01'
                    ]
                ],
                [
                    'id' => '10043',
                    'self' => 'https://jira_instance/rest/api/latest/issue/10043',
                    'key' => 'key02',
                    'fields' => [
                        'summary' => 'summary02'
                    ]
                ]
            ]
        ]);

        $exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $jira_base_url,
            $jira_project_id
        );

        $artifacts_node = $tracker_node->artifacts;
        $this->assertNotNull($artifacts_node);
        $this->assertCount(2, $artifacts_node->children());

        $artifact_node_01 = $artifacts_node->artifact[0];
        $this->assertSame("10042", (string) $artifact_node_01['id']);
        $this->assertNotNull($artifact_node_01->submitted_on);
        $this->assertNotNull($artifact_node_01->submitted_by);
        $this->assertNotNull($artifact_node_01->comments);
        $this->assertNotNull($artifact_node_01->changeset);
        $artifact_node_01_field_changes = $artifact_node_01->changeset->field_change;
        $this->assertNotNull($artifact_node_01_field_changes);
        $this->assertCount(2, $artifact_node_01_field_changes);

        $this->assertSame("URLinstance/browse/key01", (string) $artifact_node_01_field_changes[0]->value);
        $this->assertSame("summary01", (string) $artifact_node_01_field_changes[1]->value);

        $artifact_node_02 = $artifacts_node->artifact[1];
        $this->assertSame("10043", (string) $artifact_node_02['id']);
        $this->assertNotNull($artifact_node_02->submitted_on);
        $this->assertNotNull($artifact_node_02->submitted_by);
        $this->assertNotNull($artifact_node_02->comments);
        $this->assertNotNull($artifact_node_02->changeset);
        $artifact_node_02_field_changes = $artifact_node_02->changeset->field_change;
        $this->assertNotNull($artifact_node_02_field_changes);
        $this->assertCount(2, $artifact_node_02_field_changes);

        $this->assertSame("URLinstance/browse/key02", (string) $artifact_node_02_field_changes[0]->value);
        $this->assertSame("summary02", (string) $artifact_node_02_field_changes[1]->value);
    }
}
