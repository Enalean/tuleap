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

use Mockery;
use PFUser;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ArtifactLinkValue;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
use UserManager;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\FieldSnapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\Snapshot;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;
use function PHPUnit\Framework\assertFalse;

class FieldChangeXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;

    private AllTypesRetriever $all_types_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager        = Mockery::mock(UserManager::class);
        $this->all_types_retriever = new class implements AllTypesRetriever {
            public array $types = [];
            public function getAllTypes(): array
            {
                return $this->types;
            }
        };
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
            $this->all_types_retriever,
        );
    }

    public function testItExportsTheRenderedValueOfTextFieldsAsHTMLFormat(): void
    {
        $mapping = new ScalarFieldMapping(
            'description',
            'Description',
            'Fdescription',
            'description',
            'text',
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
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

        $this->assertEquals(
            <<<EOX
            <field_change type="text" field_name="description"><value format="html"><![CDATA[<h1><a name="Coin"></a>Coin</h1>

            <p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>]]></value></field_change>
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
            'Fsb',
            'sb',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id,
                    $this->getPreWiredIDGenerator($generated_tuleap_id)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self' => 'URL/rest/api/2/priority/3',
                        'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                        'name' => 'Medium',
                        'id' => (string) $jira_value_id,
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
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame((string) $generated_tuleap_id, (string) $field_change_node->value[0]);
    }

    public function testItSkipsTheValueWhenTheMappingNoLongerContainsTheIDFoundInTheChangeLog(): void
    {
        $jira_value_id = 3;

        $mapping = new ListFieldMapping(
            'sb',
            'Select Box',
            'Fsb',
            'sb',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self' => 'URL/rest/api/2/priority/3',
                        'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                        'name' => 'Medium',
                        'id' => (string) $jira_value_id,
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

        assertFalse(isset($changeset_node->field_change));
    }

    public function testItExportsTheSelectedValueInARadioButtonField(): void
    {
        $jira_value_id       = 10005;
        $generated_tuleap_id = 15;

        $mapping = new ListFieldMapping(
            'rb',
            'Radio Buttons',
            'Frb',
            'rb',
            'rb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id,
                    $this->getPreWiredIDGenerator($generated_tuleap_id)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self' => 'URL/rest/api/2/customFieldOption/10005',
                        'value' => 'test',
                        'id' => (string) $jira_value_id,
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
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame((string) $generated_tuleap_id, (string) $field_change_node->value[0]);
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
            'Fmsb',
            'msb',
            'msb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id_1,
                    $this->getPreWiredIDGenerator($generated_tuleap_id_1)
                ),
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id_2,
                    $this->getPreWiredIDGenerator($generated_tuleap_id_2)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        [
                            'self' => 'URL/rest/api/2/customFieldOption/10009',
                            'value' => 'multi1',
                            'id' => (string) $jira_value_id_1,
                        ],
                        [
                            'self' => 'URL/rest/api/2/customFieldOption/10010',
                            'value' => 'multi2',
                            'id' => (string) $jira_value_id_2,
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
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(2, $field_change_node->value);
        $this->assertSame((string) $generated_tuleap_id_1, (string) $field_change_node->value[0]);
        $this->assertSame((string) $generated_tuleap_id_2, (string) $field_change_node->value[1]);
    }

    public function testItExportsTheStatusValuesInASelectboxFieldWithTransformedIDs(): void
    {
        $jira_value_id       = 10001;
        $generated_tuleap_id = 15;

        $mapping = new ListFieldMapping(
            'status',
            'Status',
            'Fstatus',
            'status',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(
                    $jira_value_id,
                    $this->getPreWiredIDGenerator($generated_tuleap_id)
                ),
            ],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self' => 'URL/rest/api/2/status/10001',
                        'description' =>  '',
                        'iconUrl' => 'URL',
                        'name' => 'Done',
                        'id' => (string) $jira_value_id,
                        'statusCategory' =>
                            [
                                'self' => 'URL/rest/api/2/statuscategory/3',
                                'id' => 3,
                                'key' => 'done',
                                'colorName' => 'green',
                                'name' => 'Done',
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
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame((string) $generated_tuleap_id, (string) $field_change_node->value);
    }

    public function testItExportsTheUsersInASelectboxField(): void
    {
        $mapping = new ListFieldMapping(
            'assignee',
            'Assignee',
            'Fassignee',
            'assignee',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Users::TYPE,
            [],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
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

        $john_doe = Mockery::mock(PFUser::class);
        $john_doe->shouldReceive('getLdapId')->andReturn(105);
        $john_doe->shouldReceive('getId')->andReturn(105);
        $this->user_manager->shouldReceive('getUserById')->andReturn($john_doe);
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame('list', (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame('105', (string) $field_change_node->value);
    }

    public function testItExportsTheUsersInAMultiSelectboxField(): void
    {
        $mapping = new ListFieldMapping(
            'homies',
            'Homies',
            'Fhomies',
            'homies',
            'msb',
            \Tracker_FormElement_Field_List_Bind_Users::TYPE,
            [],
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
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

        $john_doe = Mockery::mock(PFUser::class);
        $john_doe->shouldReceive('getLdapId')->andReturn(105);
        $john_doe->shouldReceive('getId')->andReturn(105);

        $mysterio = Mockery::mock(PFUser::class);
        $mysterio->shouldReceive('getLdapId')->andReturn(106);
        $mysterio->shouldReceive('getId')->andReturn(106);

        $this->user_manager->shouldReceive('getUserById')->with(105)->andReturn($john_doe);
        $this->user_manager->shouldReceive('getUserById')->with(106)->andReturn($mysterio);
        $this->getExporter()->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame('list', (string) $field_change_node['type']);
        $this->assertCount(2, $field_change_node->value);
        $this->assertSame('105', (string) $field_change_node->value[0]);
        $this->assertSame('106', (string) $field_change_node->value[1]);
    }

    public function testItExportsTheLinkedIssues(): void
    {
        $this->all_types_retriever->types[] = new TypePresenter('Relates', '...', '...', true);

        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            '?',
            '?',
            \Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [
                            [
                                'id' => '10030',
                                'self' => '...',
                                'type' => [
                                    'id'   => '10003',
                                    'name' => 'Relates',
                                    'inward' => 'relates to',
                                    'outward' => 'relates to',
                                    'self' => '...',
                                ],
                                'outwardIssue' => [
                                    'id' => '10089',
                                    'key' => 'JUS-1',
                                    'self' => '...',
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
        self::assertSame(\Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame('10089', (string) $field_change_node->value[0]);
        self::assertSame('Relates', (string) $field_change_node->value[0]['nature']);
    }

    public function testItExportsTheSubTasksAsChildren(): void
    {
        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            '?',
            '?',
            \Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [],
                        [
                            [
                                'id' => '10131',
                                'key' => 'SP-31',
                                'self' => '...',
                                'fields' => [],
                            ],
                            [
                                'id' => '10132',
                                'key' => 'SP-32',
                                'self' => '...',
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
        self::assertSame(\Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(2, $field_change_node->value);
        self::assertSame('10131', (string) $field_change_node->value[0]);
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD, (string) $field_change_node->value[0]['nature']);
        self::assertSame('10132', (string) $field_change_node->value[1]);
    }

    public function testItExportsTheLinksWithoutTypesWhenTheyDontExist(): void
    {
        $mapping = new ScalarFieldMapping(
            AlwaysThereFieldsExporter::JIRA_ISSUE_LINKS_NAME,
            '?',
            '?',
            '?',
            \Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot       = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    new ArtifactLinkValue(
                        [
                            [
                                'id' => '10030',
                                'self' => '...',
                                'type' => [
                                    'id'   => '10003',
                                    'name' => 'Relates',
                                    'inward' => 'relates to',
                                    'outward' => 'relates to',
                                    'self' => '...',
                                ],
                                'outwardIssue' => [
                                    'id' => '10089',
                                    'key' => 'JUS-1',
                                    'self' => '...',
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
        self::assertSame(\Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, (string) $field_change_node['type']);
        self::assertCount(1, $field_change_node->value);
        self::assertSame('10089', (string) $field_change_node->value[0]);
        self::assertSame('', (string) $field_change_node->value[0]['nature']);
    }

    private function getPreWiredIDGenerator(int $pre_defined_id): IDGenerator
    {
        $id_generator     = new class implements IDGenerator {
            /** @var int */
            public $id;

            public function getNextId(): int
            {
                return $this->id;
            }
        };
        $id_generator->id = $pre_defined_id;
        return $id_generator;
    }
}
