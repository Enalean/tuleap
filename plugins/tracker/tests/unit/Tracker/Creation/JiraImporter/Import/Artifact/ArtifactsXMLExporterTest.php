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
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesTransformer;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserManager;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class ArtifactsXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ArtifactsXMLExporter
     */
    private $exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $wrapper;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrapper      = Mockery::mock(ClientWrapper::class);
        $this->user_manager = Mockery::mock(UserManager::class);

        $this->exporter = new ArtifactsXMLExporter(
            $this->wrapper,
            new XML_SimpleXMLCDATAFactory(),
            $this->user_manager,
            new FieldChangeXMLExporter(
                new FieldChangeDateBuilder(
                    new XML_SimpleXMLCDATAFactory()
                ),
                new FieldChangeStringBuilder(
                    new XML_SimpleXMLCDATAFactory()
                ),
                new FieldChangeTextBuilder(
                    new XML_SimpleXMLCDATAFactory()
                ),
                new FieldChangeFloatBuilder(
                    new XML_SimpleXMLCDATAFactory()
                ),
                new FieldChangeListBuilder(
                    new XML_SimpleXMLCDATAFactory(),
                    UserXMLExporter::build()
                ),
                new StatusValuesTransformer()
            ),
            new FieldChangeStringBuilder(
                new XML_SimpleXMLCDATAFactory()
            )
        );
    }

    public function testItExportsArtifacts(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('user01');

        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE
            )
        );
        $jira_project_id = 'project';
        $jira_base_url   = 'URLinstance';
        $jira_issue_name = 'Story';

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/search?jql=project%3Dproject+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields')
            ->andReturn(
                [
                    'startAt'    => 0,
                    'maxResults' => 50,
                    'total'      => 7,
                    'issues'     => [
                        [
                            'id'     => '10042',
                            'self'   => 'https://jira_instance/rest/api/latest/issue/10042',
                            'key'    => 'key01',
                            'fields' => [
                                'summary'   => 'summary01',
                                'issuetype' =>
                                    [
                                        'id' => '10004'
                                    ]
                            ]
                        ],
                        [
                            'id'     => '10043',
                            'self'   => 'https://jira_instance/rest/api/latest/issue/10043',
                            'key'    => 'key02',
                            'fields' => [
                                'summary'   => 'summary02',
                                'issuetype' =>
                                    [
                                        'id' => '10004'
                                    ]
                            ]
                        ]
                    ]
                ]
            );

        $this->exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $jira_base_url,
            $jira_project_id,
            $jira_issue_name
        );

        $this->assertXMLArtifactsContent($tracker_node);
    }

    public function testItExportsArtifactsPaginated(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('user01');

        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE
            )
        );
        $jira_project_id = 'project';
        $jira_base_url   = 'URLinstance';
        $jira_issue_name = 'Story';

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/search?jql=project%3Dproject+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields')
            ->andReturn(
                [
                    'startAt'    => 0,
                    'maxResults' => 1,
                    'total'      => 2,
                    'issues'     => [
                        [
                            'id'     => '10042',
                            'self'   => 'https://jira_instance/rest/api/latest/issue/10042',
                            'key'    => 'key01',
                            'fields' => [
                                'summary'   => 'summary01',
                                'issuetype' =>
                                    [
                                        'id' => '10004'
                                    ]
                            ]
                        ]
                    ]
                ]
            );

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/search?jql=project%3Dproject+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields&startAt=1&maxResults=1')
            ->andReturn(
                [
                    'startAt'    => 1,
                    'maxResults' => 1,
                    'total'      => 2,
                    'issues'     => [
                        [
                            'id'     => '10043',
                            'self'   => 'https://jira_instance/rest/api/latest/issue/10043',
                            'key'    => 'key02',
                            'fields' => [
                                'summary'   => 'summary02',
                                'issuetype' =>
                                    [
                                        'id' => '10004'
                                    ]
                            ]
                        ]
                    ]
                ]
            );

        $this->exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $jira_base_url,
            $jira_project_id,
            $jira_issue_name
        );

        $this->assertXMLArtifactsContent($tracker_node);
    }

    private function assertXMLArtifactsContent(SimpleXMLElement $tracker_node): void
    {
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
