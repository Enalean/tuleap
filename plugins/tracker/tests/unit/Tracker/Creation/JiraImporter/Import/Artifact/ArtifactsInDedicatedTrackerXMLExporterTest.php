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

use Psr\Log\NullLogger;
use ColinODell\PsrTestLogger\TestLogger;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactLinkTypeConverter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsInDedicatedTrackerXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ListFieldChangeInitialValueRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLValueEnhancer;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraCloudCommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentationCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAsArtifactXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ChangelogSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\CurrentSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\InitialSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\DataChangesetXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserInfoQuerier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
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

final class ArtifactsInDedicatedTrackerXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ArtifactsInDedicatedTrackerXMLExporter $exporter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ClientWrapper
     */
    private $wrapper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;

    private TestLogger $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AttachmentDownloader
     */
    private $attachment_downloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrapper               = new class extends JiraCloudClientStub {
        };
        $this->attachment_downloader = $this->createMock(AttachmentDownloader::class);
        $this->user_manager          = $this->createMock(UserManager::class);
        $this->logger                = new TestLogger();

        $forge_user = UserTestBuilder::aUser()->withId(TrackerImporterUser::ID)->withUserName('Tracker Importer')->build();

        $jira_user_retriever = new JiraUserRetriever(
            $this->logger,
            $this->user_manager,
            new JiraUserOnTuleapCache(new JiraTuleapUsersMapping(), $forge_user),
            $this->createMock(JiraUserInfoQuerier::class),
            $forge_user
        );

        $creation_state_list_value_formatter = new CreationStateListValueFormatter();
        $this->exporter                      = new ArtifactsInDedicatedTrackerXMLExporter(
            $this->wrapper,
            $this->user_manager,
            $this->logger,
            new IssueAsArtifactXMLExporter(
                new DataChangesetXMLExporter(
                    new XML_SimpleXMLCDATAFactory(),
                    new FieldChangeXMLExporter(
                        new NullLogger(),
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
                        new FieldChangeArtifactLinksBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                        ),
                        new ArtifactLinkTypeConverter(
                            new class implements AllTypesRetriever {
                                public function getAllTypes(): array
                                {
                                    return [];
                                }
                            },
                        ),
                    ),
                    new IssueSnapshotCollectionBuilder(
                        new JiraCloudChangelogEntriesBuilder(
                            $this->wrapper,
                            $this->logger
                        ),
                        new CurrentSnapshotBuilder(
                            $this->logger,
                            $creation_state_list_value_formatter,
                            $jira_user_retriever
                        ),
                        new InitialSnapshotBuilder(
                            $this->logger,
                            new ListFieldChangeInitialValueRetriever(
                                $creation_state_list_value_formatter,
                                $jira_user_retriever
                            )
                        ),
                        new ChangelogSnapshotBuilder(
                            $creation_state_list_value_formatter,
                            $this->logger,
                            $jira_user_retriever
                        ),
                        new JiraCloudCommentValuesBuilder(
                            $this->wrapper,
                            $this->logger
                        ),
                        $this->logger,
                        $jira_user_retriever
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
                $this->logger,
            ),
        );

        $this->mockChangelogForKey01();
        $this->mockChangelogForKey02();
    }

    public function testItExportsArtifacts(): void
    {
        $user = $this->buildForgeUser();

        $this->user_manager->method('getUserById')->with(91)->willReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new ScalarFieldMapping(
                'summary',
                'Summary',
                null,
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
            )
        );
        $mapping_collection->addMapping(
            new ScalarFieldMapping(
                "jira_issue_url",
                'Link to original issue',
                null,
                "Fjira_issue_url",
                "jira_issue_url",
                "string",
            ),
        );
        $jira_project_id = 'project';
        $jira_base_url   = 'URLinstance';
        $jira_issue_name = 'Story';

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22project%22+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 2,
            'issues'     => [
                [
                    'id'     => '10042',
                    'self'   => 'https://jira_instance/rest/api/3/issue/10042',
                    'key'    => 'key01',
                    'fields' => [
                        'summary'   => 'summary01',
                        'issuetype' =>
                                    [
                                        'id' => '10004',
                                    ],
                        'created' => '2020-03-25T14:10:10.823+0100',
                        'updated' => '2020-04-25T14:10:10.823+0100',
                        'creator' => [
                            'displayName' => 'Mysterio',
                            'accountId' => 'e8d453qs8f47d538s',
                        ],
                    ],
                    'renderedFields' => [],
                ],
                [
                    'id'     => '10043',
                    'self'   => 'https://jira_instance/rest/api/3/issue/10043',
                    'key'    => 'key02',
                    'fields' => [
                        'summary'   => 'summary02',
                        'issuetype' =>
                                    [
                                        'id' => '10004',
                                    ],
                        'created' => '2020-03-26T14:10:10.823+0100',
                        'updated' => '2020-04-26T14:10:10.823+0100',
                        'creator' => [
                            'displayName' => 'Mysterio',
                            'accountId' => 'e8d453qs8f47d538s',
                        ],
                    ],
                    'renderedFields' => [],
                ],
            ],
        ];

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key01/comment?expand=renderedBody&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 0,
            'comments'   => [],
        ];

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key02/comment?expand=renderedBody&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 0,
            'comments'   => [],
        ];

        $issue_collection = new IssueAPIRepresentationCollection();
        $this->exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $issue_collection,
            new LinkedIssuesCollection(),
            $jira_base_url,
            $jira_project_id,
            $jira_issue_name
        );

        self::assertXMLArtifactsContent($tracker_node);

        self::assertCount(2, $issue_collection->getIssueRepresentationCollection());
    }

    public function testItExportsArtifactsPaginated(): void
    {
        $user = $this->buildForgeUser();

        $this->user_manager->method('getUserById')->with(91)->willReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new ScalarFieldMapping(
                'summary',
                'Summary',
                null,
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
            )
        );
        $mapping_collection->addMapping(
            new ScalarFieldMapping(
                "jira_issue_url",
                "Link to original issue",
                null,
                "Fjira_issue_url",
                "jira_issue_url",
                "string",
            ),
        );
        $jira_project_id = 'project';
        $jira_base_url   = 'URLinstance';
        $jira_issue_name = 'Story';

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22project%22+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 1,
            'total'      => 2,
            'issues'     => [
                [
                    'id'     => '10042',
                    'self'   => 'https://jira_instance/rest/api/3/issue/10042',
                    'key'    => 'key01',
                    'fields' => [
                        'summary'   => 'summary01',
                        'issuetype' =>
                                    [
                                        'id' => '10004',
                                    ],
                        'created' => '2020-03-25T14:10:10.823+0100',
                        'updated' => '2020-04-25T14:10:10.823+0100',
                        'creator' => [
                            'displayName' => 'John Doe',
                            'emailAddress' => 'johndoe@example.com',
                            'accountId' => 'e8d4s2c53z',
                        ],
                    ],
                    'renderedFields' => [],
                ],
            ],
        ];

        $john_doe = $this->createMock(\PFUser::class);
        $john_doe->method('getRealName')->willReturn('John Doe');
        $john_doe->method('getUserName')->willReturn('jdoe');
        $john_doe->method('getPublicProfileUrl')->willReturn('/users/jdoe');
        $john_doe->method('getId')->willReturn('105');

        $this->user_manager->method('getAndEventuallyCreateUserByEmail')
            ->with('johndoe@example.com')
            ->willReturn([$john_doe]);

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22project%22+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields&startAt=1'] = [
            'startAt'    => 1,
            'maxResults' => 1,
            'total'      => 2,
            'issues'     => [
                [
                    'id'     => '10043',
                    'self'   => 'https://jira_instance/rest/api/3/issue/10043',
                    'key'    => 'key02',
                    'fields' => [
                        'summary'   => 'summary02',
                        'issuetype' =>
                                    [
                                        'id' => '10004',
                                    ],
                        'created' => '2020-03-26T14:10:10.823+0100',
                        'updated' => '2020-04-26T14:10:10.823+0100',
                        'creator' => [
                            'displayName' => 'Mysterio',
                            'accountId' => 'e8d4s2c53z',
                        ],
                    ],
                    'renderedFields' => [],
                ],
            ],
        ];

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key01/comment?expand=renderedBody&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 0,
            'comments'   => [],
        ];

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key02/comment?expand=renderedBody&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 0,
            'comments'   => [],
        ];

        $issue_collection = new IssueAPIRepresentationCollection();
        $this->exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $issue_collection,
            new LinkedIssuesCollection(),
            $jira_base_url,
            $jira_project_id,
            $jira_issue_name
        );

        self::assertXMLArtifactsContent($tracker_node);

        self::assertCount(2, $issue_collection->getIssueRepresentationCollection());
    }

    public function testItIgnoresArtifactsThatHaveBeenAlreadyExported(): void
    {
        $user = $this->buildForgeUser();

        $this->user_manager->method('getUserById')->with(91)->willReturn($user);

        $tracker_node       = new SimpleXMLElement('<tracker/>');
        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new ScalarFieldMapping(
                'summary',
                'Summary',
                null,
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
            )
        );
        $mapping_collection->addMapping(
            new ScalarFieldMapping(
                "jira_issue_url",
                "Link to original issue",
                null,
                "Fjira_issue_url",
                "jira_issue_url",
                "string",
            ),
        );
        $jira_project_id = 'project';
        $jira_base_url   = 'URLinstance';
        $jira_issue_name = 'Story';

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22project%22+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 1,
            'total'      => 2,
            'issues'     => [
                [
                    'id'     => '10042',
                    'self'   => 'https://jira_instance/rest/api/3/issue/10042',
                    'key'    => 'key01',
                    'fields' => [
                        'summary'   => 'summary01',
                        'issuetype' =>
                                    [
                                        'id' => '10004',
                                    ],
                        'created' => '2020-03-25T14:10:10.823+0100',
                        'updated' => '2020-04-25T14:10:10.823+0100',
                        'creator' => [
                            'displayName' => 'John Doe',
                            'emailAddress' => 'johndoe@example.com',
                            'accountId' => 'e8d4s2c53z',
                        ],
                    ],
                    'renderedFields' => [],
                ],
            ],
        ];

        $john_doe = $this->createMock(\PFUser::class);
        $john_doe->method('getRealName')->willReturn('John Doe');
        $john_doe->method('getUserName')->willReturn('jdoe');
        $john_doe->method('getPublicProfileUrl')->willReturn('/users/jdoe');
        $john_doe->method('getId')->willReturn('105');

        $this->user_manager->method('getAndEventuallyCreateUserByEmail')
            ->with('johndoe@example.com')
            ->willReturn([$john_doe]);

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22project%22+AND+issuetype%3DStory&fields=%2Aall&expand=renderedFields&startAt=1'] = [
            'startAt'    => 1,
            'maxResults' => 1,
            'total'      => 2,
            'issues'     => [
                [
                    'id'     => '10042',
                    'self'   => 'https://jira_instance/rest/api/3/issue/10042',
                    'key'    => 'key01',
                    'fields' => [
                        'summary'   => 'summary01',
                        'issuetype' =>
                            [
                                'id' => '10004',
                            ],
                        'created' => '2020-03-25T14:10:10.823+0100',
                        'updated' => '2020-04-25T14:10:10.823+0100',
                        'creator' => [
                            'displayName' => 'John Doe',
                            'emailAddress' => 'johndoe@example.com',
                            'accountId' => 'e8d4s2c53z',
                        ],
                    ],
                    'renderedFields' => [],
                ],
            ],
        ];

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key01/comment?expand=renderedBody&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 0,
            'comments'   => [],
        ];

        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key02/comment?expand=renderedBody&startAt=0'] = [
            'startAt'    => 0,
            'maxResults' => 50,
            'total'      => 0,
            'comments'   => [],
        ];

        $issue_collection = new IssueAPIRepresentationCollection();
        $this->exporter->exportArtifacts(
            $tracker_node,
            $mapping_collection,
            $issue_collection,
            new LinkedIssuesCollection(),
            $jira_base_url,
            $jira_project_id,
            $jira_issue_name
        );

        $artifacts_node = $tracker_node->artifacts;
        self::assertNotNull($artifacts_node);
        self::assertCount(1, $artifacts_node->children());

        $artifact_node_01 = $artifacts_node->artifact[0];
        self::assertSame("10042", (string) $artifact_node_01['id']);
        self::assertNotNull($artifact_node_01->submitted_on);
        self::assertNotNull($artifact_node_01->submitted_by);
        self::assertNotNull($artifact_node_01->comments);
        self::assertCount(1, $artifact_node_01->changeset);

        self::assertCount(1, $issue_collection->getIssueRepresentationCollection());

        self::assertTrue($this->logger->hasDebugThatMatches("/has already be exported, no need to export it again/"));
    }

    private function mockChangelogForKey01(): void
    {
        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key01/changelog?startAt=0'] = [
            "maxResults" => 100,
            "startAt"    => 0,
            "total"      => 0,
            "isLast"     => true,
            "values"     => [],
        ];
    }

    private function mockChangelogForKey02(): void
    {
        $this->wrapper->urls[ClientWrapper::JIRA_CORE_BASE_URL . '/issue/key02/changelog?startAt=0'] = [
            "maxResults" => 100,
            "startAt"    => 0,
            "total"      => 0,
            "isLast"     => true,
            "values"     => [],
        ];
    }

    private function assertXMLArtifactsContent(SimpleXMLElement $tracker_node): void
    {
        $artifacts_node = $tracker_node->artifacts;
        self::assertNotNull($artifacts_node);
        self::assertCount(2, $artifacts_node->children());

        $artifact_node_01 = $artifacts_node->artifact[0];
        self::assertSame("10042", (string) $artifact_node_01['id']);
        self::assertNotNull($artifact_node_01->submitted_on);
        self::assertNotNull($artifact_node_01->submitted_by);
        self::assertNotNull($artifact_node_01->comments);
        self::assertCount(1, $artifact_node_01->changeset);

        self::assertNotNull($artifact_node_01->changeset[0]);
        $artifact_node_01_field_changes_changeset_01 = $artifact_node_01->changeset[0]->field_change;
        self::assertNotNull($artifact_node_01_field_changes_changeset_01);
        self::assertCount(2, $artifact_node_01_field_changes_changeset_01);

        self::assertSame("summary01", (string) $artifact_node_01_field_changes_changeset_01[0]->value);
        self::assertSame("URLinstance/browse/key01", (string) $artifact_node_01_field_changes_changeset_01[1]->value);

        $artifact_node_02 = $artifacts_node->artifact[1];
        self::assertSame("10043", (string) $artifact_node_02['id']);
        self::assertNotNull($artifact_node_02->submitted_on);
        self::assertNotNull($artifact_node_02->submitted_by);
        self::assertNotNull($artifact_node_02->comments);
        self::assertCount(1, $artifact_node_02->changeset);

        self::assertNotNull($artifact_node_02->changeset[0]);
        $artifact_node_02_field_changes_changeset_01 = $artifact_node_02->changeset[0]->field_change;
        self::assertNotNull($artifact_node_02_field_changes_changeset_01);
        self::assertCount(2, $artifact_node_02_field_changes_changeset_01);

        self::assertSame("summary02", (string) $artifact_node_02_field_changes_changeset_01[0]->value);
        self::assertSame("URLinstance/browse/key02", (string) $artifact_node_02_field_changes_changeset_01[1]->value);
    }

    private function buildForgeUser(): \PFUser
    {
        return UserTestBuilder::aUser()->withId(TrackerImporterUser::ID)->withUserName('forge__user01')->build();
    }
}
