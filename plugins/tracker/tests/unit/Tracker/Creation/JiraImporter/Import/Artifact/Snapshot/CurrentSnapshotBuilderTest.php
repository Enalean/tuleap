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

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class CurrentSnapshotBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildsSnapshotForIssueCurrentData(): void
    {
        $logger                = Mockery::mock(LoggerInterface::class);
        $jira_author_retriever = Mockery::mock(JiraAuthorRetriever::class);
        $builder               = new CurrentSnapshotBuilder(
            $logger,
            new CreationStateListValueFormatter(),
            $jira_author_retriever
        );

        $user                          = Mockery::mock(PFUser::class);
        $john_doe                      = Mockery::mock(PFUser::class);
        $mysterio                      = Mockery::mock(PFUser::class);
        $jira_issue_api                = $this->buildIssueAPIResponse();
        $jira_field_mapping_collection = $this->buildFieldMappingCollection();

        $john_doe->shouldReceive('getId')->andReturn(105);
        $mysterio->shouldReceive('getId')->andReturn(106);
        $jira_author_retriever->shouldReceive('getAssignedTuleapUser')->with('e6a7dae9')->andReturn($john_doe);
        $jira_author_retriever->shouldReceive('getAssignedTuleapUser')->with('d45a6r4f')->andReturn($mysterio);
        $logger->shouldReceive('debug');

        $snapshot = $builder->buildCurrentSnapshot(
            $user,
            $jira_issue_api,
            $jira_field_mapping_collection
        );

        $this->assertSame(1587820210, $snapshot->getDate()->getTimestamp());
        $this->assertSame($user, $snapshot->getUser());
        $this->assertCount(4, $snapshot->getAllFieldsSnapshot());

        foreach ($snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            $field_id = $field_snapshot->getFieldMapping()->getJiraFieldId();
            if ($field_id === 'summary') {
                $this->assertSame("summary01", $field_snapshot->getValue());
                $this->assertNull($field_snapshot->getRenderedValue());
            } elseif ($field_id === 'issuetype') {
                $this->assertSame(['id' => '10004'], $field_snapshot->getValue());
                $this->assertNull($field_snapshot->getRenderedValue());
            } elseif ($field_id === 'assignee') {
                $this->assertSame(['id' => '105'], $field_snapshot->getValue());
                $this->assertNull($field_snapshot->getRenderedValue());
            } elseif ($field_id === 'homies') {
                $this->assertSame(
                    [
                        ['id' => '105'],
                        ['id' => '106'],
                    ],
                    $field_snapshot->getValue()
                );
                $this->assertNull($field_snapshot->getRenderedValue());
            } else {
                $this->fail("Unexpected field $field_id in mapping");
            }
        }
    }

    private function buildIssueAPIResponse(): IssueAPIRepresentation
    {
        return IssueAPIRepresentation::buildFromAPIResponse(
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
                    'assignee' => [
                        'accountId'    => 'e6a7dae9',
                        'displayName'  => 'John Doe',
                        'emailAddress' => 'john.doe@example.com'
                    ],
                    'homies' => [
                        [
                            'accountId'    => 'e6a7dae9',
                            'displayName'  => 'John Doe',
                            'emailAddress' => 'john.doe@example.com'
                        ], [
                            'accountId'    => 'd45a6r4f',
                            'displayName'  => 'Mysterio',
                            'emailAddress' => 'myster.io@example.com'
                        ]
                    ]
                ],
                'renderedFields' => []
            ]
        );
    }

    private function buildFieldMappingCollection(): FieldMappingCollection
    {
        $collection = new FieldMappingCollection();
        $collection->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'Summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                null
            )
        );
        $collection->addMapping(
            new FieldMapping(
                'issuetype',
                'Fissuetype',
                'Issue Type',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            )
        );
        $collection->addMapping(
            new FieldMapping(
                'assignee',
                'Fassignee',
                'Assignee',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Users::TYPE
            )
        );
        $collection->addMapping(
            new FieldMapping(
                'homies',
                'Fhomies',
                'Homies',
                Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Users::TYPE
            )
        );

        return $collection;
    }
}
