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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ArtifactLinkValue;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\FieldSnapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\Snapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\Import\Artifact\GetExistingArtifactLinkTypesStub;
use Tuleap\Tracker\Test\Stub\XML\IDGeneratorStub;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

#[DisableReturnValueGenerationForTestDoubles]
final class FieldChangeXMLExporterTest extends TestCase
{
    private UserManager&MockObject $user_manager;
    private GetExistingArtifactLinkTypes $type_converter;

    protected function setUp(): void
    {
        $this->user_manager   = $this->createMock(UserManager::class);
        $this->type_converter = GetExistingArtifactLinkTypesStub::build(null);
    }

    private function getExporter(): FieldChangeXMLExporter
    {
        return new FieldChangeXMLExporter(
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
                new UserXMLExporter(
                    $this->user_manager,
                    new UserXMLExportedCollection(
                        new XML_RNGValidator(),
                        new XML_SimpleXMLCDATAFactory()
                    )
                )
            ),
            new FieldChangeFileBuilder(),
            new FieldChangeArtifactLinksBuilder(
                new XML_SimpleXMLCDATAFactory(),
            ),
            $this->type_converter,
        );
    }

    public function testItExportsTheRenderedValueOfTextFieldsAsHTMLFormat(): void
    {
        $mapping = new ScalarFieldMapping(
            'description',
            'Description',
            null,
            'Fdescription',
            'description',
            'text',
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    "h1. Coin\r\n\r\nLorem *ipsum* _doloret_ plop.",
                    "<h1><a name=\"Coin\"></a>Coin</h1>\n\n<p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>"
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node,
        );

        self::assertEquals(
            <<<EOX
            <field_change type="text" field_name="description"><value format="html"><![CDATA[<h1><a name="Coin"></a>Coin</h1>

            <p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>]]></value></field_change>
            EOX,
            $changeset_node->field_change->asXML()
        );
    }

    public function testItExportsTheValueOfTextFieldsAsTextFormatWhenNoRenderedValue(): void
    {
        $mapping = new ScalarFieldMapping(
            'description',
            'Description',
            null,
            'Fdescription',
            'description',
            'text',
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    "h1. Coin\r\n\r\nLorem *ipsum* _doloret_ plop.",
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node,
        );

        self::assertEquals(
            <<<EOX
            <field_change type="text" field_name="description"><value format="text"><![CDATA[h1. Coin\r\n\r\nLorem *ipsum* _doloret_ plop.]]></value></field_change>
            EOX,
            $changeset_node->field_change->asXML()
        );
    }

    public function testItDefaultsToEmptyStringWhenValueOfTextFieldIsAnArrayInsteadOfAStringAndThereIsNoRenderedValue(): void
    {
        $mapping = new ScalarFieldMapping(
            'description',
            'Description',
            null,
            'Fdescription',
            'description',
            'text',
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    ['id' => ''],
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node,
        );

        self::assertEquals(
            <<<EOX
            <field_change type="text" field_name="description"><value format="text"><![CDATA[]]></value></field_change>
            EOX,
            $changeset_node->field_change->asXML()
        );
    }

    public function testItExportsTheSelectedValueInASelectBoxField(): void
    {
        $jira_value_id       = 3;
        $generated_tuleap_id = 15;

        $mapping = new ListFieldMapping(
            'sb',
            'Select Box',
            null,
            'Fsb',
            'sb',
            'sb',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id,
                    IDGeneratorStub::withId($generated_tuleap_id)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self'    => 'URL/rest/api/2/priority/3',
                        'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                        'name'    => 'Medium',
                        'id'      => (string) $jira_value_id,
                    ],
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame((string) $generated_tuleap_id, (string) $field_change_node->value[0]);
    }

    public function testItSkipsTheValueWhenTheMappingNoLongerContainsTheIDFoundInTheChangeLog(): void
    {
        $jira_value_id = 3;

        $mapping = new ListFieldMapping(
            'sb',
            'Select Box',
            null,
            'Fsb',
            'sb',
            'sb',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self'    => 'URL/rest/api/2/priority/3',
                        'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                        'name'    => 'Medium',
                        'id'      => (string) $jira_value_id,
                    ],
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        self::assertFalse(isset($changeset_node->field_change));
    }

    public function testItExportsTheSelectedValueInARadioButtonField(): void
    {
        $jira_value_id       = 10005;
        $generated_tuleap_id = 15;

        $mapping = new ListFieldMapping(
            'rb',
            'Radio Buttons',
            null,
            'Frb',
            'rb',
            'rb',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id,
                    IDGeneratorStub::withId($generated_tuleap_id)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self'  => 'URL/rest/api/2/customFieldOption/10005',
                        'value' => 'test',
                        'id'    => (string) $jira_value_id,
                    ],
                    null
                ),
            ],
            null
        );

        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame((string) $generated_tuleap_id, (string) $field_change_node->value[0]);
    }

    public function testItExportsTheSelectedValuesInAMultiSelectboxField(): void
    {
        $jira_value_id_1       = 10009;
        $jira_value_id_2       = 10010;
        $generated_tuleap_id_1 = 15;
        $generated_tuleap_id_2 = 16;

        $mapping = new ListFieldMapping(
            'msb',
            'Multi Select Box',
            null,
            'Fmsb',
            'msb',
            'msb',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id_1,
                    IDGeneratorStub::withId($generated_tuleap_id_1)
                ),
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id_2,
                    IDGeneratorStub::withId($generated_tuleap_id_2)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        [
                            'self'  => 'URL/rest/api/2/customFieldOption/10009',
                            'value' => 'multi1',
                            'id'    => (string) $jira_value_id_1,
                        ],
                        [
                            'self'  => 'URL/rest/api/2/customFieldOption/10010',
                            'value' => 'multi2',
                            'id'    => (string) $jira_value_id_2,
                        ],

                    ],
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(2, $field_change_node->value);
        self::assertSame((string) $generated_tuleap_id_1, (string) $field_change_node->value[0]);
        self::assertSame((string) $generated_tuleap_id_2, (string) $field_change_node->value[1]);
    }

    public function testItExportsTheSelectedValuesInACheckboxField(): void
    {
        $jira_value_id_1       = 10009;
        $jira_value_id_2       = 10010;
        $generated_tuleap_id_1 = 15;
        $generated_tuleap_id_2 = 16;

        $mapping = new ListFieldMapping(
            'cb',
            'CheckBox',
            null,
            'Fcb',
            'cb',
            'cb',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id_1,
                    IDGeneratorStub::withId($generated_tuleap_id_1)
                ),
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id_2,
                    IDGeneratorStub::withId($generated_tuleap_id_2)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        [
                            'self'  => 'URL/rest/api/2/customFieldOption/10009',
                            'value' => 'multi1',
                            'id'    => (string) $jira_value_id_1,
                        ],
                        [
                            'self'  => 'URL/rest/api/2/customFieldOption/10010',
                            'value' => 'multi2',
                            'id'    => (string) $jira_value_id_2,
                        ],

                    ],
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(2, $field_change_node->value);
        self::assertSame((string) $generated_tuleap_id_1, (string) $field_change_node->value[0]);
        self::assertSame((string) $generated_tuleap_id_2, (string) $field_change_node->value[1]);
    }

    public function testItExportsTheStatusValuesInASelectboxFieldWithTransformedIDs(): void
    {
        $jira_value_id       = 10001;
        $generated_tuleap_id = 15;

        $mapping = new ListFieldMapping(
            'status',
            'Status',
            null,
            'Fstatus',
            'status',
            'sb',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id,
                    IDGeneratorStub::withId($generated_tuleap_id)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self'           => 'URL/rest/api/2/status/10001',
                        'description'    => '',
                        'iconUrl'        => 'URL',
                        'name'           => 'Done',
                        'id'             => (string) $jira_value_id,
                        'statusCategory' =>
                            [
                                'self'      => 'URL/rest/api/2/statuscategory/3',
                                'id'        => 3,
                                'key'       => 'done',
                                'colorName' => 'green',
                                'name'      => 'Done',
                            ],
                    ],
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame((string) $generated_tuleap_id, (string) $field_change_node->value);
    }

    public function testItExportsTheUsersInASelectboxField(): void
    {
        $mapping = new ListFieldMapping(
            'assignee',
            'Assignee',
            null,
            'Fassignee',
            'assignee',
            'sb',
            Tracker_FormElement_Field_List_Bind_Users::TYPE,
            [],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'id' => '105',
                    ],
                    null
                ),
            ],
            null
        );

        $john_doe = UserTestBuilder::aUser()->withId(105)->withLdapId('105')->build();
        $this->user_manager->method('getUserById')->willReturn($john_doe);
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame('105', (string) $field_change_node->value);
    }

    public function testItExportsTheUsersInAMultiSelectboxField(): void
    {
        $mapping = new ListFieldMapping(
            'homies',
            'Homies',
            null,
            'Fhomies',
            'homies',
            'msb',
            Tracker_FormElement_Field_List_Bind_Users::TYPE,
            [],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [

                        ['id' => '105'],
                        ['id' => '106'],
                    ],
                    null
                ),
            ],
            null
        );

        $john_doe = UserTestBuilder::aUser()->withId(105)->withLdapId('105')->build();
        $mysterio = UserTestBuilder::aUser()->withId(106)->withLdapId('106')->build();
        $this->user_manager->method('getUserById')->willReturnCallback(static fn(int|string $id) => match ((int) $id) {
            105 => $john_doe,
            106 => $mysterio,
        });
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('list', (string) $field_change_node['type']);
        self::assertCount(2, $field_change_node->value);
        self::assertSame('105', (string) $field_change_node->value[0]);
        self::assertSame('106', (string) $field_change_node->value[1]);
    }

    public function testItExportsTheLinkedIssues(): void
    {
        $this->type_converter = GetExistingArtifactLinkTypesStub::build(new TypePresenter('Relates', 'relates to', 'relates to', true));

        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            null,
            '?',
            '?',
            Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [
                            [
                                'id'           => '10030',
                                'self'         => '...',
                                'type'         => [
                                    'id'      => '10003',
                                    'name'    => 'Relates',
                                    'inward'  => 'relates to',
                                    'outward' => 'relates to',
                                    'self'    => '...',
                                ],
                                'outwardIssue' => [
                                    'id'     => '10089',
                                    'key'    => 'JUS-1',
                                    'self'   => '...',
                                    'fields' => [],

                                ],
                            ],
                        ],
                        [],
                    ),
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame(Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame('10089', (string) $field_change_node->value[0]);
        self::assertSame('Relates', (string) $field_change_node->value[0]['nature']);
    }

    public function testItExportsTheLinkedIssuesWithTransformedLinkType(): void
    {
        $this->type_converter = GetExistingArtifactLinkTypesStub::build(new TypePresenter('Problem_Incident', 'causes', 'is caused by', true));

        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            null,
            '?',
            '?',
            Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [
                            [
                                'id'           => '10030',
                                'self'         => '...',
                                'type'         => [
                                    'id'      => '10003',
                                    'name'    => 'Problem/Incident',
                                    'inward'  => 'causes',
                                    'outward' => 'is caused by',
                                    'self'    => '...',
                                ],
                                'outwardIssue' => [
                                    'id'     => '10089',
                                    'key'    => 'JUS-1',
                                    'self'   => '...',
                                    'fields' => [],

                                ],
                            ],
                        ],
                        [],
                    ),
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame(Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame('10089', (string) $field_change_node->value[0]);
        self::assertSame('Problem_Incident', (string) $field_change_node->value[0]['nature']);
    }

    public function testItExportsTheSubTasksAsChildren(): void
    {
        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            null,
            '?',
            '?',
            Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [],
                        [
                            [
                                'id'     => '10131',
                                'key'    => 'SP-31',
                                'self'   => '...',
                                'fields' => [],
                            ],
                            [
                                'id'     => '10132',
                                'key'    => 'SP-32',
                                'self'   => '...',
                                'fields' => [],
                            ],
                        ],
                    ),
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame(Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(2, $field_change_node->value);
        self::assertSame('10131', (string) $field_change_node->value[0]);
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD, (string) $field_change_node->value[0]['nature']);
        self::assertSame('10132', (string) $field_change_node->value[1]);
    }

    public function testItExportsTheLinksWithoutTypesWhenTheyDontExist(): void
    {
        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            null,
            '?',
            '?',
            Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::buildWithDefaults(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [
                            [
                                'id'           => '10030',
                                'self'         => '...',
                                'type'         => [
                                    'id'      => '10003',
                                    'name'    => 'Relates',
                                    'inward'  => 'relates to',
                                    'outward' => 'relates to',
                                    'self'    => '...',
                                ],
                                'outwardIssue' => [
                                    'id'     => '10089',
                                    'key'    => 'JUS-1',
                                    'self'   => '...',
                                    'fields' => [],

                                ],
                            ],
                        ],
                        [],
                    ),
                    null
                ),
            ],
            null
        );
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame(Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame('10089', (string) $field_change_node->value[0]);
        self::assertSame('', (string) $field_change_node->value[0]['nature']);
    }

    public function testItExportsOpenListFieldWithValueAsArray(): void
    {
        $mapping = new ListFieldMapping(
            'labels',
            'Labels',
            null,
            'Flabels',
            'labels',
            'tbl',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildFromIDAndName(
                    0,
                    'label01'
                ),
                JiraFieldAPIAllowedValueRepresentation::buildFromIDAndName(
                    0,
                    'label02'
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::aUser()->build(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    ['label01'],
                    null
                ),
            ],
            null
        );

        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('open_list', (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertFalse(isset($field_change_node->value[0]['format']));
        self::assertSame('label01', (string) $field_change_node->value[0]);
    }

    public function testItExportsOpenListFieldWithValueAsString(): void
    {
        $mapping = new ListFieldMapping(
            'labels',
            'Labels',
            null,
            'Flabels',
            'labels',
            'tbl',
            Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildFromIDAndName(
                    0,
                    'label01'
                ),
                JiraFieldAPIAllowedValueRepresentation::buildFromIDAndName(
                    0,
                    'label02'
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            UserTestBuilder::aUser()->build(),
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    'label01 label02',
                    null
                ),
            ],
            null
        );

        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        self::assertSame('open_list', (string) $field_change_node['type']);
        self::assertCount(2, $field_change_node->value);
        self::assertFalse(isset($field_change_node->value[0]['format']));
        self::assertSame('label01', (string) $field_change_node->value[0]);
        self::assertFalse(isset($field_change_node->value[1]['format']));
        self::assertSame('label02', (string) $field_change_node->value[1]);
    }
}
