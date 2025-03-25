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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\GetExistingArtifactLinkTypes;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;

#[DisableReturnValueGenerationForTestDoubles]
final class CurrentSnapshotBuilderTest extends TestCase
{
    public function testItBuildsSnapshotForIssueCurrentData(): void
    {
        $logger              = new TestLogger();
        $jira_user_retriever = $this->createMock(JiraUserRetriever::class);
        $builder             = new CurrentSnapshotBuilder(
            $logger,
            new CreationStateListValueFormatter(),
            $jira_user_retriever
        );

        $user                          = UserTestBuilder::buildWithDefaults();
        $john_doe                      = UserTestBuilder::buildWithId(105);
        $mysterio                      = UserTestBuilder::buildWithId(106);
        $jira_issue_api                = $this->buildIssueAPIResponse();
        $jira_field_mapping_collection = $this->buildFieldMappingCollection();

        $jira_user_retriever->method('retrieveUserFromAPIData')
            ->willReturnCallback(static fn(array $data) => match (true) {
                $data['accountId'] === 'e6a7dae9' && $data['displayName'] === 'John Doe' && $data['emailAddress'] === 'john.doe@example.com'  => $john_doe,
                $data['accountId'] === 'd45a6r4f' && $data['displayName'] === 'Mysterio' && $data['emailAddress'] === 'myster.io@example.com' => $mysterio,
            });

        $snapshot = $builder->buildCurrentSnapshot(
            $user,
            $jira_issue_api,
            $jira_field_mapping_collection,
            new LinkedIssuesCollection(),
        );

        self::assertSame(1587820210, $snapshot->getDate()->getTimestamp());
        self::assertSame($user, $snapshot->getUser());
        self::assertCount(4, $snapshot->getAllFieldsSnapshot());

        foreach ($snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            $field_id = $field_snapshot->getFieldMapping()->getJiraFieldId();
            if ($field_id === 'summary') {
                self::assertSame('summary01', $field_snapshot->getValue());
                self::assertNull($field_snapshot->getRenderedValue());
            } elseif ($field_id === 'issuetype') {
                self::assertSame(['id' => '10004'], $field_snapshot->getValue());
                self::assertNull($field_snapshot->getRenderedValue());
            } elseif ($field_id === 'assignee') {
                self::assertSame(['id' => '105'], $field_snapshot->getValue());
                self::assertNull($field_snapshot->getRenderedValue());
            } elseif ($field_id === 'homies') {
                self::assertSame(
                    [
                        ['id' => '105'],
                        ['id' => '106'],
                    ],
                    $field_snapshot->getValue()
                );
                self::assertNull($field_snapshot->getRenderedValue());
            } else {
                self::fail("Unexpected field $field_id in mapping");
            }
        }
        self::assertTrue($logger->hasDebugRecords());
    }

    private function buildIssueAPIResponse(): IssueAPIRepresentation
    {
        return IssueAPIRepresentation::buildFromAPIResponse(
            [
                'id'             => '10042',
                'self'           => 'https://jira_instance/rest/api/3/issue/10042',
                'key'            => 'key01',
                'fields'         => [
                    'summary'   => 'summary01',
                    'issuetype' =>
                        [
                            'id' => '10004',
                        ],
                    'created'   => '2020-03-25T14:10:10.823+0100',
                    'updated'   => '2020-04-25T14:10:10.823+0100',
                    'assignee'  => [
                        'accountId'    => 'e6a7dae9',
                        'displayName'  => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                    'homies'    => [
                        [
                            'accountId'    => 'e6a7dae9',
                            'displayName'  => 'John Doe',
                            'emailAddress' => 'john.doe@example.com',
                        ], [
                            'accountId'    => 'd45a6r4f',
                            'displayName'  => 'Mysterio',
                            'emailAddress' => 'myster.io@example.com',
                        ],
                    ],
                ],
                'renderedFields' => [],
            ]
        );
    }

    private function buildFieldMappingCollection(): FieldMappingCollection
    {
        $collection = new FieldMappingCollection();
        $collection->addMapping(
            new ScalarFieldMapping(
                'summary',
                'Summary',
                null,
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'issuetype',
                'Issue Type',
                null,
                'Fissuetype',
                'issuetype',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'assignee',
                'Assignee',
                null,
                'Fassignee',
                'assignee',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'homies',
                'Homies',
                null,
                'Fhomies',
                'homies',
                Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        return $collection;
    }

    public function testItAddsSubTasksToIssueLinksForArtifactLinks(): void
    {
        $jira_field_mapping_collection = new FieldMappingCollection();
        $jira_field_mapping_collection->addMapping(
            new ScalarFieldMapping(
                AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
                'Links',
                null,
                'F001',
                AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
                Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
            )
        );

        $builder = new CurrentSnapshotBuilder(
            new NullLogger(),
            new CreationStateListValueFormatter(),
            $this->createStub(JiraUserRetriever::class)
        );

        $snapshot_owner = UserTestBuilder::aUser()->build();

        $snapshot = $builder->buildCurrentSnapshot(
            $snapshot_owner,
            $this->buildIssueAPIResponseWithLinksAndSubTasks(),
            $jira_field_mapping_collection,
            new LinkedIssuesCollection(),
        );

        $fields = $snapshot->getAllFieldsSnapshot();
        self::assertCount(1, $fields);
        self::assertEquals(new ArtifactLinkValue(['issue links representation'], ['subtask representation']), $fields[0]->getValue());
    }

    private function buildIssueAPIResponseWithLinksAndSubTasks(): IssueAPIRepresentation
    {
        return IssueAPIRepresentation::buildFromAPIResponse(
            [
                'id'             => '10042',
                'self'           => 'https://jira_instance/rest/api/3/issue/10042',
                'key'            => 'key01',
                'fields'         => [
                    'updated'    => '2020-04-25T14:10:10.823+0100',
                    'issuelinks' => [
                        'issue links representation',
                    ],
                    'subtasks'   => [
                        'subtask representation',
                    ],
                ],
                'renderedFields' => [],
            ]
        );
    }

    public function testItAddsTheLinkedIssuesThatAreDefinedInOuterScope(): void
    {
        $jira_field_mapping_collection = new FieldMappingCollection();
        $jira_field_mapping_collection->addMapping(
            new ScalarFieldMapping(
                AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
                'Links',
                null,
                'F001',
                AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
                Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
            )
        );

        $builder = new CurrentSnapshotBuilder(
            new NullLogger(),
            new CreationStateListValueFormatter(),
            $this->createStub(JiraUserRetriever::class)
        );

        $linked_issues = (new LinkedIssuesCollection())
            ->withChild('SP-36', '10005');

        $snapshot = $builder->buildCurrentSnapshot(
            UserTestBuilder::aUser()->build(),
            IssueAPIRepresentation::buildFromAPIResponse(
                [
                    'id'             => '10042',
                    'self'           => 'https://jira_instance/rest/api/3/issue/10042',
                    'key'            => 'SP-36',
                    'fields'         => [
                        'updated'    => '2020-04-25T14:10:10.823+0100',
                        'issuelinks' => [],
                        'subtasks'   => [],
                    ],
                    'renderedFields' => [],
                ]
            ),
            $jira_field_mapping_collection,
            $linked_issues
        );

        $fields = $snapshot->getAllFieldsSnapshot();
        self::assertCount(1, $fields);
        self::assertEquals(
            new ArtifactLinkValue(
                [
                    [
                        'type'         => [
                            'name' => GetExistingArtifactLinkTypes::FAKE_JIRA_TYPE_TO_RECREATE_CHILDREN,
                        ],
                        'outwardIssue' => [
                            'id' => '10005',
                        ],
                    ],
                ],
                []
            ),
            $fields[0]->getValue()
        );
    }
}
