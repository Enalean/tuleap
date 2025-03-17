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
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;

#[DisableReturnValueGenerationForTestDoubles]
final class ChangelogSnapshotBuilderTest extends TestCase
{
    public function testItBuildsASnapshotFromChangelogEntry(): void
    {
        $jira_user_retriever = $this->createMock(JiraUserRetriever::class);
        $builder             = new ChangelogSnapshotBuilder(
            new CreationStateListValueFormatter(),
            new NullLogger(),
            $jira_user_retriever
        );

        $user                          = UserTestBuilder::buildWithDefaults();
        $john_doe                      = UserTestBuilder::buildWithId(105);
        $mysterio                      = UserTestBuilder::buildWithId(106);
        $changelog_entry               = $this->buildChangelogEntry();
        $jira_field_mapping_collection = $this->buildFieldMappingCollection();
        $current_snapshot              = $this->buildCurrentSnapshot($user);
        $attachment_collection         = new AttachmentCollection([
            new Attachment(
                10007,
                'file01.png',
                'image/png',
                'URL',
                30
            ),
            new Attachment(
                10008,
                'file02.gif',
                'image/gif',
                'URL2',
                3056
            ),
        ]);

        $jira_user_retriever->method('retrieveJiraAuthor')->willReturn($user);
        $jira_user_retriever->method('getAssignedTuleapUser')->willReturnCallback(static fn(string $id) => match ($id) {
            'e8a7dbae5' => $john_doe,
            'a7e8b9c5'  => $mysterio,
        });

        $snapshot = $builder->buildSnapshotFromChangelogEntry(
            $current_snapshot,
            $changelog_entry,
            $attachment_collection,
            $jira_field_mapping_collection
        );

        self::assertSame($user, $snapshot->getUser());
        self::assertSame(1585141810, $snapshot->getDate()->getTimestamp());
        self::assertCount(12, $snapshot->getAllFieldsSnapshot());

        self::assertNull($snapshot->getFieldInSnapshot('environment'));
        self::assertSame('9', $snapshot->getFieldInSnapshot('customfield_10036')->getValue());
        self::assertSame(
            [
                ['id' => '10009'],
                ['id' => '10010'],
            ],
            $snapshot->getFieldInSnapshot('customfield_10040')->getValue()
        );

        self::assertSame(
            '*aaaaaaaaa*',
            $snapshot->getFieldInSnapshot('description')->getValue()
        );

        self::assertSame(
            '<p>aaaaaaaaa</p>',
            $snapshot->getFieldInSnapshot('description')->getRenderedValue()
        );

        self::assertSame(
            '*def*',
            $snapshot->getFieldInSnapshot('textfield')->getValue()
        );

        self::assertSame(
            'lorem ipsum',
            $snapshot->getFieldInSnapshot('customfield_10081')->getValue()
        );

        self::assertSame(
            ['id' => 'Neque porro'],
            $snapshot->getFieldInSnapshot('customfield_10059')->getValue()
        );

        self::assertNull($snapshot->getFieldInSnapshot('textfield')->getRenderedValue());

        self::assertSame(
            [10008],
            $snapshot->getFieldInSnapshot('attachment')->getValue()
        );

        self::assertSame(
            '2020-03-25',
            $snapshot->getFieldInSnapshot('datepicker')->getValue()
        );

        self::assertSame(
            ['id' => '105'],
            $snapshot->getFieldInSnapshot('assignee')->getValue()
        );

        self::assertSame(
            [
                ['id' => '105'],
                ['id' => '106'],
            ],
            $snapshot->getFieldInSnapshot('homies')->getValue()
        );

        $expected_snapshot_value_when_no_user_value = '100';
        self::assertSame(
            ['id' => $expected_snapshot_value_when_no_user_value],
            $snapshot->getFieldInSnapshot('customfield_10057')->getValue(),
        );

        $expected_snapshot_value_when_no_static_value = '';
        self::assertSame(
            ['id' => $expected_snapshot_value_when_no_static_value],
            $snapshot->getFieldInSnapshot('customfield_10058')->getValue(),
        );

        self::assertNull($snapshot->getFieldInSnapshot('versions'));
        self::assertNull($snapshot->getFieldInSnapshot('fixVersions'));
        self::assertNull($snapshot->getFieldInSnapshot('components'));
        self::assertNull($snapshot->getFieldInSnapshot('customfield_10100'));
        self::assertNull($snapshot->getFieldInSnapshot('customfield_10101'));
        self::assertNull($snapshot->getFieldInSnapshot('customfield_10102'));
    }

    private function buildCurrentSnapshot(PFUser $user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:10:10.823+0100'),
            [
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'description',
                        'Description',
                        null,
                        'Fdescription',
                        'description',
                        'text',
                    ),
                    '*aaaaaaaaa*',
                    '<p>aaaaaaaaa</p>'
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'textfield',
                        'Text Field',
                        null,
                        'Ftextfield',
                        'textfield',
                        'text',
                    ),
                    '*text area v2*',
                    '<p>text area v2</p>'
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'attachment',
                        'Attachment',
                        null,
                        'Fattachment',
                        'attachments',
                        'file',
                    ),
                    [
                        [
                            'id' => '10007',
                        ],
                    ],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        'assignee',
                        'Assignee',
                        null,
                        'Fassignee',
                        'assignee',
                        'sb',
                        Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        [],
                    ),
                    ['id' => '105'],
                    null
                ),
                new FieldSnapshot(
                    new ListFieldMapping(
                        'homies',
                        'Homies',
                        null,
                        'Fhomies',
                        'homies',
                        'msb',
                        Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        [],
                    ),
                    [
                        ['id' => '105'],
                        ['id' => '106'],
                    ],
                    null
                ),
                new FieldSnapshot(
                    $this->getVersionMapping(),
                    [
                        ['id' => '10003'],
                        ['id' => '10004'],
                    ],
                    null,
                ),
                new FieldSnapshot(
                    $this->getFixVersionsMapping(),
                    [
                        ['id' => '10005'],
                        ['id' => '10006'],
                    ],
                    null,
                ),
                new FieldSnapshot(
                    $this->getComponentsMapping(),
                    [
                        ['id' => '10005'],
                    ],
                    null,
                ),
                new FieldSnapshot(
                    $this->getCustomMultiversionMapping(),
                    [
                        ['id' => '10010'],
                        ['id' => '10011'],
                    ],
                    null,
                ),
                new FieldSnapshot(
                    $this->getCustomVersionMapping(),
                    [
                        ['id' => '10012'],
                    ],
                    null,
                ),
                new FieldSnapshot(
                    $this->getCustomMulticheckboxesMapping(),
                    [
                        ['id' => '10030'],
                        ['id' => '10031'],
                        ['id' => '10032'],
                    ],
                    null,
                ),
            ],
            null
        );
    }

    private function buildChangelogEntry(): ChangelogEntryValueRepresentation
    {
        return JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
            [
                'id'      => '100',
                'created' => '2020-03-25T14:10:10.823+0100',
                'items'   => [
                    [
                        'fieldId'    => 'customfield_10036',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => null,
                        'toString'   => '9',
                    ],
                    [
                        'fieldId'    => 'customfield_10040',
                        'from'       => '[10009]',
                        'fromString' => 'mulit1',
                        'to'         => '[10009, 10010]',
                        'toString'   => 'mulit1,multi2',
                    ],
                    [
                        'fieldId'    => 'environment',
                        'from'       => null,
                        'fromString' => "\r\n----\r\n",
                        'to'         => null,
                        'toString'   => "----\r\n",
                    ],
                    [
                        'fieldId'    => 'description',
                        'from'       => null,
                        'fromString' => 'aaaaaaaaaaa',
                        'to'         => '{"id":"ari:cloud:jira:d63a8014-ba58-4b58-b22d-eb1d85d56f3d:issuefieldvalue/10006/description","version":"1"}',
                        'toString'   => '*aaaaaaaaa*',
                    ],
                    [
                        'fieldId'    => 'textfield',
                        'from'       => null,
                        'fromString' => 'abc',
                        'to'         => null,
                        'toString'   => '*def*',
                    ],
                    [
                        'fieldId'    => 'attachment',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => '10008',
                        'toString'   => 'file02.gif',
                    ],
                    [

                        'fieldId'    => 'datepicker',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => '2020-03-25',
                        'toString'   => '25/Mar/20',
                    ],
                    [
                        'fieldId'    => 'assignee',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => 'e8a7dbae5',
                        'toString'   => 'John Doe',
                    ],
                    [
                        'fieldId'    => 'homies',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => '[e8a7dbae5, a7e8b9c5]',
                        'toString'   => '[John Doe, Mysterio]',
                    ],
                    [
                        'fieldId'    => 'versions',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => '10003',
                        'toString'   => 'Release 1.0',
                    ],
                    [
                        'fieldId'    => 'fixVersions',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => '10013',
                        'toString'   => 'Release 2.0',
                    ],
                    [
                        [
                            'fieldId'    => 'components',
                            'from'       => null,
                            'fromString' => null,
                            'to'         => '10000',
                            'toString'   => 'Comp 01',
                        ],
                    ],
                    [
                        [
                            'fieldId'    => 'customfield_10100',
                            'from'       => null,
                            'fromString' => null,
                            'to'         => '10020',
                            'toString'   => '[v1]',
                        ],
                    ],
                    [
                        [
                            'fieldId'    => 'customfield_10101',
                            'from'       => null,
                            'fromString' => null,
                            'to'         => '10020',
                            'toString'   => '[v1]',
                        ],
                    ],
                    [
                        [
                            'fieldId'    => 'customfield_10102',
                            'from'       => null,
                            'fromString' => null,
                            'to'         => '10030',
                            'toString'   => '[test1]',
                        ],
                    ],
                    [
                        'fieldId'    => 'customfield_10081',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => '',
                        'toString'   => 'lorem ipsum',
                    ],
                    [
                        'fieldId'    => 'customfield_10057',
                        'from'       => '10045',
                        'fromString' => 'f52a7e97',
                        'to'         => null,
                        'toString'   => '',
                    ],
                    [
                        'fieldId'    => 'customfield_10058',
                        'from'       => '10023',
                        'fromString' => '06. PKI',
                        'to'         => null,
                        'toString'   => '',
                    ],
                    [
                        'fieldId'    => 'customfield_10059',
                        'from'       => null,
                        'fromString' => null,
                        'to'         => 'Neque porro',
                        'toString'   => '',
                    ],
                ],
                'author'  => [
                    'accountId'    => 'e8a7dbae5',
                    'displayName'  => 'John Doe',
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
                'customfield_10036',
                'Field 01',
                null,
                'Fcustomfield_10036',
                'customfield_10036',
                'float',
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'status',
                'status',
                null,
                'Fstatus',
                'status',
                'sb',
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'customfield_10040',
                'customfield_10040',
                null,
                'Fcustomfield_10040',
                'Field 02',
                'msb',
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                []
            ),
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                'description',
                'Description',
                null,
                'Fdescription',
                'description',
                'text',
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                'textfield',
                'Text Field',
                null,
                'Ftextfield',
                'textfield',
                'text',
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                'attachment',
                'Attachment',
                null,
                'Fattachment',
                'attachments',
                'file',
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'assignee',
                'Assignee',
                null,
                'Fassignee',
                'assignee',
                'sb',
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
                'msb',
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                'datepicker',
                'Date Picker',
                null,
                'Fdatepicker',
                'patepicker',
                'date',
            ),
        );
        $collection->addMapping(
            new ScalarFieldMapping(
                'customfield_10081',
                'customfield_10081',
                null,
                'Fcustomfield_10081',
                'customfield_10081',
                'text',
            )
        );
        $collection->addMapping(
            new ListFieldMapping(
                'customfield_10057',
                'customfield_10057',
                null,
                'Fcustomfield_10057',
                'customfield_10057',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        $collection->addMapping(
            new ListFieldMapping(
                'customfield_10058',
                'customfield_10058',
                null,
                'Fcustomfield_10058',
                'customfield_10058',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );

        $collection->addMapping(
            new ListFieldMapping(
                'customfield_10059',
                'customfield_10059',
                null,
                'Fcustomfield_10059',
                'customfield_10059',
                Tracker_FormElementFactory::FIELD_CHECKBOX_TYPE,
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );

        $collection->addMapping($this->getVersionMapping());
        $collection->addMapping($this->getFixVersionsMapping());
        $collection->addMapping($this->getCustomMultiversionMapping());
        $collection->addMapping($this->getCustomVersionMapping());
        $collection->addMapping($this->getCustomMulticheckboxesMapping());

        return $collection;
    }

    private function getVersionMapping(): ListFieldMapping
    {
        return new ListFieldMapping(
            'versions',
            'Affected versions',
            null,
            'Fversions',
            'versions',
            Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );
    }

    private function getFixVersionsMapping(): ListFieldMapping
    {
        return new ListFieldMapping(
            'fixVersions',
            'Fixed in versions',
            null,
            'Ffixversions',
            'fixversions',
            Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );
    }

    private function getComponentsMapping(): ListFieldMapping
    {
        return new ListFieldMapping(
            'components',
            'Components',
            null,
            'Fcomponents',
            'components',
            Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );
    }

    private function getCustomMultiversionMapping(): ListFieldMapping
    {
        return new ListFieldMapping(
            'customfield_10100',
            'Multi versions',
            'com.atlassian.jira.plugin.system.customfieldtypes:multiversion',
            'Fcustomfield_10100',
            'customfield_10100',
            Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );
    }

    private function getCustomVersionMapping(): ListFieldMapping
    {
        return new ListFieldMapping(
            'customfield_10101',
            'Version',
            'com.atlassian.jira.plugin.system.customfieldtypes:version',
            'Fcustomfield_10101',
            'customfield_10101',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );
    }

    private function getCustomMulticheckboxesMapping(): ListFieldMapping
    {
        return new ListFieldMapping(
            'customfield_10102',
            'Multi Checkboxes',
            'com.atlassian.jira.plugin.system.customfieldtypes:multicheckboxes',
            'Fcustomfield_10102',
            'customfield_10102',
            Tracker_FormElementFactory::FIELD_CHECKBOX_TYPE,
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );
    }
}
