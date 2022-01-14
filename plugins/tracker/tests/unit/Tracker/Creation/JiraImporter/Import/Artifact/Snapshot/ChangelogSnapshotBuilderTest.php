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
use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;

class ChangelogSnapshotBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildsASnapshotFromChangelogEntry(): void
    {
        $logger              = new NullLogger();
        $jira_user_retriever = Mockery::mock(JiraUserRetriever::class);
        $builder             = new ChangelogSnapshotBuilder(
            new CreationStateListValueFormatter(),
            $logger,
            $jira_user_retriever
        );

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
                ),
            ]
        );

        $john_doe->shouldReceive('getId')->andReturn(105);
        $mysterio->shouldReceive('getId')->andReturn(106);
        $jira_user_retriever->shouldReceive('retrieveJiraAuthor')->andReturn($user);
        $jira_user_retriever->shouldReceive('getAssignedTuleapUser')->with('e8a7dbae5')->andReturn($john_doe);
        $jira_user_retriever->shouldReceive('getAssignedTuleapUser')->with('a7e8b9c5')->andReturn($mysterio);

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
                ['id' => '106'],
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
                    new ScalarFieldMapping(
                        "description",
                        "Description",
                        "Fdescription",
                        "description",
                        "text",
                    ),
                    "*aaaaaaaaa*",
                    "<p>aaaaaaaaa</p>"
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        "textfield",
                        "Text Field",
                        "Ftextfield",
                        "textfield",
                        "text",
                    ),
                    "*text area v2*",
                    "<p>text area v2</p>"
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
                        [
                            'id' => "10007",
                        ],
                    ],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        'assignee',
                        'Assignee',
                        'Fassignee',
                        'assignee',
                        'sb',
                        \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        [],
                    ),
                    ['id' => '105'],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        'homies',
                        'Homies',
                        'Fhomies',
                        'homies',
                        'msb',
                        \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        [],
                    ),
                    [
                        ['id' => '105'],
                        ['id' => '106'],
                    ],
                    null
                ),
            ],
            null
        );
    }

    private function buildChangelogEntry(): ChangelogEntryValueRepresentation
    {
        return JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
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
                    1 => [
                        "fieldId"    => "customfield_10040",
                        "from"       => "[10009]",
                        "fromString" => "mulit1",
                        "to"         => "[10009, 10010]",
                        "toString"   => "mulit1,multi2",
                    ],
                    2 => [
                        "fieldId"    => "environment",
                        "from"       => null,
                        "fromString" => "\r\n----\r\n",
                        "to"         => null,
                        "toString"   => "----\r\n",
                    ],
                    3 => [
                        "fieldId"    => "description",
                        "from"       => null,
                        "fromString" => "aaaaaaaaaaa",
                        "to"         => '{"id":"ari:cloud:jira:d63a8014-ba58-4b58-b22d-eb1d85d56f3d:issuefieldvalue/10006/description","version":"1"}',
                        "toString"   => "*aaaaaaaaa*",
                    ],
                    4 => [
                        "fieldId"    => "textfield",
                        "from"       => null,
                        "fromString" => "abc",
                        "to"         => null,
                        "toString"   => "*def*",
                    ],
                    5 => [
                        "fieldId"    => "attachment",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "10008",
                        "toString"   => "file02.gif",
                    ],
                    6 => [

                        "fieldId"    => "datepicker",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "2020-03-25",
                        "toString"   => "25/Mar/20",
                    ],
                    7 => [
                        "fieldId"    => "assignee",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "e8a7dbae5",
                        "toString"   => "John Doe",
                    ],
                    8 => [
                        "fieldId"    => "homies",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "[e8a7dbae5, a7e8b9c5]",
                        "toString"   => "[John Doe, Mysterio]",
                    ],
                    9 => [
                        "fieldId"    => "versions",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "10003",
                        "toString"   => "Release 1.0",
                    ],
                    10 => [
                        "fieldId"    => "fixVersions",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => "10013",
                        "toString"   => "Release 2.0",
                    ],
                ],
                'author' => [
                    'accountId' => 'e8a7dbae5',
                    'displayName' => 'John Doe',
                    'emailAddress' => 'john.doe@example.com',
                ],
            ]
        );
    }

    private function buildFieldMappingCollection(): FieldMappingCollection
    {
        $collection = new FieldMappingCollection();
        $collection->addMapping(
            new ScalarFieldMapping(
                "customfield_10036",
                "Field 01",
                "Fcustomfield_10036",
                "customfield_10036",
                "float",
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                "status",
                "status",
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
                "customfield_10040",
                "Fcustomfield_10040",
                "Field 02",
                "msb",
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                []
            ),
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "description",
                "Description",
                "Fdescription",
                "description",
                "text",
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "textfield",
                "Text Field",
                "Ftextfield",
                "textfield",
                "text",
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "attachment",
                "Attachment",
                "Fattachment",
                "attachments",
                "file",
            )
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
            )
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
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                "datepicker",
                "Date Picker",
                "Fdatepicker",
                "patepicker",
                "date",
            ),
        );
        $collection->addMapping(
            new ListFieldMapping(
                'versions',
                'Affected versions',
                'Fversions',
                'versions',
                \Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'fixVersions',
                'Fixed in version',
                'Ffixversions',
                'fixversions',
                \Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );

        return $collection;
    }
}
