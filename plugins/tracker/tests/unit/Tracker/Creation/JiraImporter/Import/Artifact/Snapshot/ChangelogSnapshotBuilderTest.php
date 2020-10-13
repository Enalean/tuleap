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

use DateTimeImmutable;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class ChangelogSnapshotBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildsASnapshotFromChangelogEntry(): void
    {
        $logger                = Mockery::mock(LoggerInterface::class);
        $jira_author_retriever = Mockery::mock(JiraAuthorRetriever::class);
        $builder               = new ChangelogSnapshotBuilder(
            new CreationStateListValueFormatter(),
            $logger,
            $jira_author_retriever
        );

        $logger->shouldReceive('debug');

        $user                          = Mockery::mock(PFUser::class);
        $john_doe                      = Mockery::mock(PFUser::class);
        $mysterio                      = Mockery::mock(PFUser::class);
        $changelog_entry               = $this->buildChangelogEntry();
        $jira_field_mapping_collection = $this->buildFieldMappingCollection();
        $current_snapshot              = $this->buildCurrentSnapshot($user);
        $attachment_collection         = new AttachmentCollection(
            [
                new Attachment(
                    10007,
                    "file01.png",
                    "image/png",
                    "URL",
                    30
                ),
                new Attachment(
                    10008,
                    "file02.gif",
                    "image/gif",
                    "URL2",
                    3056
                )
            ]
        );

        $john_doe->shouldReceive('getId')->andReturn(105);
        $mysterio->shouldReceive('getId')->andReturn(106);
        $jira_author_retriever->shouldReceive('retrieveJiraAuthor')->andReturn($user);
        $jira_author_retriever->shouldReceive('getAssignedTuleapUser')->with('e8a7dbae5')->andReturn($john_doe);
        $jira_author_retriever->shouldReceive('getAssignedTuleapUser')->with('a7e8b9c5')->andReturn($mysterio);

        $snapshot = $builder->buildSnapshotFromChangelogEntry(
            $current_snapshot,
            $changelog_entry,
            $attachment_collection,
            $jira_field_mapping_collection
        );

        $this->assertSame($user, $snapshot->getUser());
        $this->assertSame(1585141810, $snapshot->getDate()->getTimestamp());
        $this->assertCount(8, $snapshot->getAllFieldsSnapshot());

        $this->assertNull($snapshot->getFieldInSnapshot('environment'));
        $this->assertSame("9", $snapshot->getFieldInSnapshot('customfield_10036')->getValue());
        $this->assertSame(
            [
                ['id' => '10009'],
                ['id' => '10010'],
            ],
            $snapshot->getFieldInSnapshot('customfield_10040')->getValue()
        );

        $this->assertSame(
            "*aaaaaaaaa*",
            $snapshot->getFieldInSnapshot('description')->getValue()
        );

        $this->assertSame(
            "<p>aaaaaaaaa</p>",
            $snapshot->getFieldInSnapshot('description')->getRenderedValue()
        );

        $this->assertSame(
            "*def*",
            $snapshot->getFieldInSnapshot('textfield')->getValue()
        );

        $this->assertNull($snapshot->getFieldInSnapshot('textfield')->getRenderedValue());

        $this->assertSame(
            [10008],
            $snapshot->getFieldInSnapshot('attachment')->getValue()
        );

        $this->assertSame(
            "2020-03-25",
            $snapshot->getFieldInSnapshot('datepicker')->getValue()
        );

        $this->assertSame(
            ['id' => '105'],
            $snapshot->getFieldInSnapshot('assignee')->getValue()
        );

        $this->assertSame(
            [
                ['id' => '105'],
                ['id' => '106']
            ],
            $snapshot->getFieldInSnapshot('homies')->getValue()
        );
    }

    private function buildCurrentSnapshot(PFUser $user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:10:10.823+0100"),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "description",
                        "Fdescription",
                        "Description",
                        "text",
                        null
                    ),
                    "*aaaaaaaaa*",
                    "<p>aaaaaaaaa</p>"
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "textfield",
                        "Ftextfield",
                        "Text Field",
                        "text",
                        null
                    ),
                    "*text area v2*",
                    "<p>text area v2</p>"
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "attachment",
                        "Fattachment",
                        "Attachments",
                        "file",
                        null
                    ),
                    [
                        [
                            'id' => "10007"
                        ]
                    ],
                    null
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        'assignee',
                        'Fassignee',
                        'Assignee',
                        'sb',
                        \Tracker_FormElement_Field_List_Bind_Users::TYPE
                    ),
                    ['id' => '105'],
                    null
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        'homies',
                        'Fhomies',
                        'Homies',
                        'msb',
                        \Tracker_FormElement_Field_List_Bind_Users::TYPE
                    ),
                    [
                        ['id' => '105'],
                        ['id' => '106'],
                    ],
                    null
                )
            ],
            null
        );
    }

    private function buildChangelogEntry(): ChangelogEntryValueRepresentation
    {
        return ChangelogEntryValueRepresentation::buildFromAPIResponse(
            [
                "id" => "100",
                "created" => "2020-03-25T14:10:10.823+0100",
                "items" => [
                    0 => [
                        "fieldId"    => "customfield_10036",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => null,
                        "toString"   => "9"
                    ],
                    1 => [
                        "fieldId"    => "customfield_10040",
                        "from"       => "[10009]",
                        "fromString" => "mulit1",
                        "to"         => "[10009, 10010]",
                        "toString"   => "mulit1,multi2"
                    ],
                    2 => [
                        "fieldId"    => "environment",
                        "from"       => null,
                        "fromString" => "\r\n----\r\n",
                        "to"         => null,
                        "toString"   => "----\r\n"
                    ],
                    3 => [
                        "fieldId"    => "description",
                        "from"       => null,
                        "fromString" => "aaaaaaaaaaa",
                        "to"         => '{"id":"ari:cloud:jira:d63a8014-ba58-4b58-b22d-eb1d85d56f3d:issuefieldvalue/10006/description","version":"1"}',
                        "toString"   => "*aaaaaaaaa*"
                    ],
                    4 => [
                        "fieldId"    => "textfield",
                        "from"       => null,
                        "fromString" => "abc",
                        "to"         => null,
                        "toString"   => "*def*"
                    ],
                    5 => [
                        "fieldId"    => "attachment",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "10008",
                        "toString"   => "file02.gif"
                    ],
                    6 => [

                        "fieldId"    => "datepicker",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "2020-03-25",
                        "toString"   => "25/Mar/20"
                    ],
                    7 => [
                        "fieldId"    => "assignee",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "e8a7dbae5",
                        "toString"   => "John Doe"
                    ],
                    8 => [
                        "fieldId"    => "homies",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "e8a7dbae5, a7e8b9c5",
                        "toString"   => "John Doe, Mysterio"
                    ]
                ],
                'author' => [
                    'accountId' => 'e8a7dbae5',
                    'displayName' => 'John Doe',
                    'emailAddress' => 'john.doe@example.com'
                ]
            ]
        );
    }

    private function buildFieldMappingCollection(): FieldMappingCollection
    {
        $collection = new FieldMappingCollection();
        $collection->addMapping(
            new FieldMapping(
                "customfield_10036",
                "Fcustomfield_10036",
                "Field 01",
                "float",
                null
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "status",
                "Fstatus",
                "status",
                "sb",
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "customfield_10040",
                "Fcustomfield_10040",
                "Field 02",
                "msb",
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            ),
        );
        $collection->addMapping(
            new FieldMapping(
                "description",
                "Fdescription",
                "Description",
                "text",
                null
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "textfield",
                "Ftextfield",
                "Text Field",
                "text",
                null
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "attachment",
                "Fattachment",
                "Attachments",
                "file",
                null
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "assignee",
                "Fassignee",
                "Assignee",
                "sb",
                \Tracker_FormElement_Field_List_Bind_Users::TYPE
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "homies",
                "Fhomies",
                "Homies",
                "msb",
                \Tracker_FormElement_Field_List_Bind_Users::TYPE
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "datepicker",
                "Fdatepicker",
                "Date picker",
                "date",
                null
            ),
        );

        return $collection;
    }
}
