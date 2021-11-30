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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ListFieldChangeInitialValueRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;

class InitialSnapshotBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsSnapshotForInitialChangeset(): void
    {
        $logger              = Mockery::mock(LoggerInterface::class);
        $jira_user_retriever = Mockery::mock(JiraUserRetriever::class);
        $generator           = new InitialSnapshotBuilder(
            $logger,
            new ListFieldChangeInitialValueRetriever(
                new CreationStateListValueFormatter(),
                $jira_user_retriever
            )
        );

        $logger->shouldReceive('debug');

        $user           = Mockery::mock(PFUser::class);
        $jira_issue_api = IssueAPIRepresentation::buildFromAPIResponse(
            [
                "id" => "10001",
                "key" => "key01",
                "fields" => [
                    "created" => "2020-03-25T14:10:10.823+0100",
                    "updated" => "2020-04-25T14:10:10.823+0100",
                    "customfield_10036" => "11",
                    "customfield_10045" => "02/Feb/21",
                    "status" => "10001",
                    "customfield_10040" => [
                        "10009", "10010",
                    ],
                    "description" => "*dsdsdsds*\n\n*qdsdsqdsqdsq*\n\n\n\n*dsqdsdsq*",
                ],
                'renderedFields' => [
                    "description" => "<p>dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq</p>",
                ],
            ]
        );

        $jira_base_url = 'URL';

        $current_snapshot         = $this->buildCurrentSnapshot($user);
        $changelog_entries        = $this->buildChangelogEntries();
        $field_mapping_collection = $this->buildFieldMappingCollection();
        $attachment_collection    = new AttachmentCollection(
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
                ),
            ]
        );

        $mysterio = Mockery::mock(PFUser::class);
        $mysterio->shouldReceive('getId')->andReturn('104');
        $john_doe = Mockery::mock(PFUser::class);
        $john_doe->shouldReceive('getId')->andReturn('105');

        $jira_user_retriever->shouldReceive('getAssignedTuleapUser')->andReturnValues([$mysterio, $john_doe]);

        $initial_snapshot = $generator->buildInitialSnapshot(
            $user,
            $current_snapshot,
            $changelog_entries,
            $field_mapping_collection,
            $jira_issue_api,
            $attachment_collection,
            $jira_base_url
        );

        self::assertNull($initial_snapshot->getFieldInSnapshot("environment"));
        self::assertNull($initial_snapshot->getFieldInSnapshot("customfield_10036"));

        self::assertSame($user, $initial_snapshot->getUser());
        self::assertSame(1585141810, $initial_snapshot->getDate()->getTimestamp());

        self::assertSame("URL/browse/key01", $initial_snapshot->getFieldInSnapshot('jira_issue_url')->getValue());
        self::assertSame(['id' => "10000"], $initial_snapshot->getFieldInSnapshot('status')->getValue());
        self::assertSame([['id' => "10009"]], $initial_snapshot->getFieldInSnapshot('customfield_10040')->getValue());
        self::assertSame('2021-01-01', $initial_snapshot->getFieldInSnapshot('customfield_10045')->getValue());
        self::assertSame("dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq", $initial_snapshot->getFieldInSnapshot('description')->getValue());
        self::assertSame(['id' => '104'], $initial_snapshot->getFieldInSnapshot('assignee')->getValue());
        self::assertSame(
            [
                ['id' => '105'],
            ],
            $initial_snapshot->getFieldInSnapshot('homies')->getValue()
        );

        self::assertNull($initial_snapshot->getFieldInSnapshot('description')->getRenderedValue());

        self::assertSame(
            [10008],
            $initial_snapshot->getFieldInSnapshot('attachment')->getValue()
        );
    }

    private function buildFieldMappingCollection(): FieldMappingCollection
    {
        $collection = new FieldMappingCollection(new FieldAndValueIDGenerator());
        $collection->addMapping(
            new ListFieldMapping(
                "status",
                "Status",
                "Fstatus",
                "status",
                "sb",
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                "customfield_10040",
                "Field 02",
                "Fcustomfield_10040",
                "customfield_10040",
                "msb",
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "customfield_10041",
                "Start date",
                "Fcustomfield_10041",
                "customfield_10041",
                "date",
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "description",
                "Description",
                "Fdescription",
                "description",
                "text",
            ),
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "jira_issue_url",
                "Link to original issue",
                "Fjira_issue_url",
                "jira_issue_url",
                "string",
            ),
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "attachment",
                "Attachment",
                "Fattachment",
                "attachments",
                "file",
            ),
        );
        $collection->addMapping(
            new ListFieldMapping(
                "assignee",
                "Assignee",
                "Fassignee",
                "assignee",
                "sb",
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            ),
        );
        $collection->addMapping(
            new ListFieldMapping(
                "homies",
                "Homies",
                "Fhomies",
                "homies",
                "msb",
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            ),
        );

        return $collection;
    }

    private function buildChangelogEntries(): array
    {
        return [
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "100",
                    "created" => "2020-03-25T14:10:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10036",
                            "from"       => null,
                            "fromString" => null,
                            "to"         => null,
                            "toString"   => "9",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "101",
                    "created" => "2020-03-25T14:11:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10036",
                            "from"       => null,
                            "fromString" => "9",
                            "to"         => null,
                            "toString"   => "11",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "102",
                    "created" => "2020-03-25T14:12:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "status",
                            "from"       => "10000",
                            "fromString" => "To Do",
                            "to"         => "10001",
                            "toString"   => "Done",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "103",
                    "created" => "2020-03-25T14:13:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10040",
                            "from"       => "[10009]",
                            "fromString" => "mulit1",
                            "to"         => "[10009, 10010]",
                            "toString"   => "mulit1,multi2",
                        ],
                        1 => [
                            "fieldId"    => "environment",
                            "from"       => null,
                            "fromString" => "\r\n----\r\n",
                            "to"         => null,
                            "toString"   => "----\r\n",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "104",
                    "created" => "2020-03-25T14:14:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "attachment",
                            "from"       => null,
                            "fromString" => null,
                            "to"         => "10007",
                            "toString"   => "file.png",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "105",
                    "created" => "2020-03-25T14:15:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "description",
                            "from"       => null,
                            "fromString" => "dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq",
                            "to"         => null,
                            "toString"   => "*dsdsdsds*\n\n*qdsdsqdsqdsq*\n\n\n\n*dsqdsdsq*",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "106",
                    "created" => "2020-03-25T14:15:11.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "assignee",
                            "from"       => "e8d9s4f123ds",
                            "fromString" => "Mysterio",
                            "to"         => "e485s54bacs5",
                            "toString"   => "John (The great) Doe",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "107",
                    "created" => "2020-03-25T14:15:11.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "homies",
                            "from"       => 'e485s54bacs5',
                            "fromString" => 'John (The great) Doe',
                            "to"         => "e485s54bacs5, a5b1d5f6e78b",
                            "toString"   => "John (The great) Doe, Mysterio (The mysterious)",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "102",
                    "created" => "2020-03-25T14:15:12.823+0100",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10045",
                            "from"       => "2021-01-01",
                            "fromString" => "01/Jan/21",
                            "to"         => "2021-02-02",
                            "toString"   => "02/Feb/21",
                        ],
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
        ];
    }

    private function buildCurrentSnapshot(PFUser $user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:14:10.823+0100"),
            [
                new FieldSnapshot(
                    new ListFieldMapping(
                        "status",
                        "Status",
                        "Fstatus",
                        "status",
                        "sb",
                        \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                        [],
                    ),
                    [
                        'id' => "10000",
                    ],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        "customfield_10040",
                        "Field 02",
                        "Fcustomfield_10040",
                        "customfield_10040",
                        "msb",
                        \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                        [],
                    ),
                    [
                        ['id' => "10009"],
                    ],
                    null
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        "customfield_10045",
                        "Start Date",
                        "Fcustomfield_10045",
                        "customfield_10045",
                        "date",
                    ),
                    "2021-02-02",
                    null
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        "description",
                        "Description",
                        "Fdescription",
                        "description",
                        "text",
                    ),
                    "dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq",
                    null
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        "attachment",
                        "Attachment",
                        "Fattachment",
                        "attachments",
                        "file",
                    ),
                    [
                        "id" => "10007",
                    ],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        "assignee",
                        "Assignee",
                        "Fassignee",
                        "assignee",
                        "sb",
                        \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        [],
                    ),
                    [
                        "id" => "104",
                    ],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        "homies",
                        "Homies",
                        "Fhomies",
                        "homies",
                        "msb",
                        \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        [],
                    ),
                    [
                        ["id" => "104"],
                        ["id" => "105"],
                    ],
                    null
                ),
            ],
            null
        );
    }
}
