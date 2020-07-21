<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ListFieldChangeInitialValueRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLValueEnhancer;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserInfoQuerier;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ChangelogSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\CurrentSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\InitialSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\DataChangesetXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesTransformer;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
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

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AttachmentDownloader
     */
    private $attachment_downloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrapper               = Mockery::mock(ClientWrapper::class);
        $this->attachment_downloader = Mockery::mock(AttachmentDownloader::class);
        $this->user_manager          = Mockery::mock(UserManager::class);
        $this->logger                = Mockery::mock(LoggerInterface::class);

        $forge_user = \Mockery::mock(\PFUser::class);
        $forge_user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);
        $forge_user->shouldReceive('getUserName')->andReturn('Tracker Importer');

        $jira_author_retriever = new JiraAuthorRetriever(
            $this->logger,
            $this->user_manager,
            new JiraUserOnTuleapCache(new JiraTuleapUsersMapping(), $forge_user),
            Mockery::mock(JiraUserInfoQuerier::class),
            $forge_user
        );
        $creation_state_list_value_formatter = new CreationStateListValueFormatter();
        $this->exporter                      = new ArtifactsXMLExporter(
            $this->wrapper,
            $this->user_manager,
            new DataChangesetXMLExporter(
                new XML_SimpleXMLCDATAFactory(),
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
                    new FieldChangeFileBuilder(),
                    new StatusValuesTransformer()
                ),
                new IssueSnapshotCollectionBuilder(
                    new ChangelogEntriesBuilder(
                        $this->wrapper,
                        $this->logger
                    ),
                    new CurrentSnapshotBuilder(
                        $this->logger,
                        $creation_state_list_value_formatter,
                        $jira_author_retriever
                    ),
                    new InitialSnapshotBuilder(
                        $this->logger,
                        new ListFieldChangeInitialValueRetriever(
                            $creation_state_list_value_formatter,
                            $jira_author_retriever
                        )
                    ),
                    new ChangelogSnapshotBuilder(
                        $creation_state_list_value_formatter,
                        $this->logger,
                        $jira_author_retriever
                    ),
                    new CommentValuesBuilder(
                        $this->wrapper,
                        $this->logger
                    ),
                    $this->logger,
                    $jira_author_retriever
                ),
                new CommentXMLExporter(
                    new XML_SimpleXMLCDATAFactory(),
                    new CommentXMLValueEnhancer()
                ),
                $this->logger
            ),
            new AttachmentCollectionBuilder(),
            new AttachmentXMLExporter(
                $this->attachment_downloader,
                new XML_SimpleXMLCDATAFactory()
            ),
            $this->logger
        );

        $this->mockChangelogForKey01();
        $this->mockChangelogForKey02();

        $this->logger->shouldReceive('debug');
    }

    public function testItExportsArtifacts(): void
    {
        $user = Mockery::mock(TrackerImporterUser::class);
        $user->shouldReceive('getUserName')->andReturn('forge__user01');
        $user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);

        $this->user_manager->shouldReceive('getUserById')->with(91)->andReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
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
        $mapping_collection->addMapping(
            new FieldMapping(
                "jira_issue_url",
                "Fjira_issue_url",
                "Link to original issue",
                "string",
                null
            ),
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
                                    ],
                                'created' => '2020-03-25T14:10:10.823+0100',
                                'updated' => '2020-04-25T14:10:10.823+0100',
                                'creator' => [
                                    'displayName' => 'Mysterio',
                                    'accountId' => 'e8d453qs8f47d538s'
                                ]
                            ],
                            'renderedFields' => []
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
                                    ],
                                'created' => '2020-03-26T14:10:10.823+0100',
                                'updated' => '2020-04-26T14:10:10.823+0100',
                                'creator' => [
                                    'displayName' => 'Mysterio',
                                    'accountId' => 'e8d453qs8f47d538s'
                                ]
                            ],
                            'renderedFields' => []
                        ]
                    ]
                ]
            );

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/issue/key01/comment?expand=renderedBody')
            ->andReturn([
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 0,
                'comments'   => []
            ]);

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/issue/key02/comment?expand=renderedBody')
            ->andReturn([
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 0,
                'comments'   => []
            ]);

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
        $user = Mockery::mock(TrackerImporterUser::class);
        $user->shouldReceive('getUserName')->andReturn('forge__user01');
        $user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);

        $this->user_manager->shouldReceive('getUserById')->with(91)->andReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
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
        $mapping_collection->addMapping(
            new FieldMapping(
                "jira_issue_url",
                "Fjira_issue_url",
                "Link to original issue",
                "string",
                null
            ),
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
                                    ],
                                'created' => '2020-03-25T14:10:10.823+0100',
                                'updated' => '2020-04-25T14:10:10.823+0100',
                                'creator' => [
                                    'displayName' => 'John Doe',
                                    'emailAddress' => 'johndoe@example.com',
                                    'accountId' => 'e8d4s2c53z'
                                ]
                            ],
                            'renderedFields' => []
                        ]
                    ]
                ]
            );

        $john_doe = Mockery::mock(\PFUser::class);
        $john_doe->shouldReceive('getRealName')->andReturn('John Doe');
        $john_doe->shouldReceive('getUserName')->andReturn('jdoe');
        $john_doe->shouldReceive('getPublicProfileUrl')->andReturn('/users/jdoe');
        $john_doe->shouldReceive('getId')->andReturn('105');

        $this->user_manager->shouldReceive('getAllUsersByEmail')
            ->with('johndoe@example.com')
            ->andReturn([$john_doe]);

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
                                    ],
                                'created' => '2020-03-26T14:10:10.823+0100',
                                'updated' => '2020-04-26T14:10:10.823+0100',
                                'creator' => [
                                    'displayName' => 'Mysterio',
                                    'accountId' => 'e8d4s2c53z'
                                ]
                            ],
                            'renderedFields' => []
                        ]
                    ]
                ]
            );

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/issue/key01/comment?expand=renderedBody')
            ->andReturn([
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 0,
                'comments'   => []
            ]);

        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/issue/key02/comment?expand=renderedBody')
            ->andReturn([
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 0,
                'comments'   => []
            ]);

        $this->exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $jira_base_url,
            $jira_project_id,
            $jira_issue_name
        );

        $this->assertXMLArtifactsContent($tracker_node);
    }

    private function mockChangelogForKey01(): void
    {
        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/issue/key01/changelog')
            ->andReturn([
                "maxResults" => 100,
                "startAt"    => 0,
                "total"      => 0,
                "isLast"     => true,
                "values"     => []
            ]);
    }

    private function mockChangelogForKey02(): void
    {
        $this->wrapper
            ->shouldReceive('getUrl')
            ->with('/issue/key02/changelog')
            ->andReturn([
                "maxResults" => 100,
                "startAt"    => 0,
                "total"      => 0,
                "isLast"     => true,
                "values"     => []
            ]);
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
        $this->assertCount(1, $artifact_node_01->changeset);

        $this->assertNotNull($artifact_node_01->changeset[0]);
        $artifact_node_01_field_changes_changeset_01 = $artifact_node_01->changeset[0]->field_change;
        $this->assertNotNull($artifact_node_01_field_changes_changeset_01);
        $this->assertCount(2, $artifact_node_01_field_changes_changeset_01);

        $this->assertSame("summary01", (string) $artifact_node_01_field_changes_changeset_01[0]->value);
        $this->assertSame("URLinstance/browse/key01", (string) $artifact_node_01_field_changes_changeset_01[1]->value);

        $artifact_node_02 = $artifacts_node->artifact[1];
        $this->assertSame("10043", (string) $artifact_node_02['id']);
        $this->assertNotNull($artifact_node_02->submitted_on);
        $this->assertNotNull($artifact_node_02->submitted_by);
        $this->assertNotNull($artifact_node_02->comments);
        $this->assertCount(1, $artifact_node_02->changeset);

        $this->assertNotNull($artifact_node_02->changeset[0]);
        $artifact_node_02_field_changes_changeset_01 = $artifact_node_02->changeset[0]->field_change;
        $this->assertNotNull($artifact_node_02_field_changes_changeset_01);
        $this->assertCount(2, $artifact_node_02_field_changes_changeset_01);

        $this->assertSame("summary02", (string) $artifact_node_02_field_changes_changeset_01[0]->value);
        $this->assertSame("URLinstance/browse/key02", (string) $artifact_node_02_field_changes_changeset_01[1]->value);
    }
}
