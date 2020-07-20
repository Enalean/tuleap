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
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use UserManager;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\FieldSnapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\Snapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesTransformer;
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

class FieldChangeXMLExporterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var FieldChangeXMLExporter
     */
    private $exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->exporter     = new FieldChangeXMLExporter(
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
            new StatusValuesTransformer()
        );
    }

    public function testItExportsTheRenderedValueOfTextFieldsAsHTMLFormat(): void
    {
        $mapping = new FieldMapping(
            'description',
            'Fdescription',
            'Description',
            'text',
            null
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    "h1. Coin\r\n\r\nLorem *ipsum* _doloret_ plop.",
                    "<h1><a name=\"Coin\"></a>Coin</h1>\n\n<p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>"
                )
            ],
            null
        );
        $this->exporter->exportFieldChanges(
            $snapshot,
            $changeset_node,
        );

        $this->assertEquals(
            <<<EOX
            <field_change type="text" field_name="Description"><value format="html"><![CDATA[<h1><a name="Coin"></a>Coin</h1>

            <p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>]]></value></field_change>
            EOX,
            $changeset_node->field_change->asXML()
        );
    }

    public function testItExportsTheSelectedValueInASelectBoxField(): void
    {
        $mapping = new FieldMapping(
            'sb',
            'Fsb',
            'Select Box',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self' => 'URL/rest/api/2/priority/3',
                        'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                        'name' => 'Medium',
                        'id' => '3',
                    ],
                    null
                )
            ],
            null
        );
        $this->exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame("3", (string) $field_change_node->value[0]);
    }

    public function testItExportsTheSelectedValueInARadioButtonField(): void
    {
        $mapping = new FieldMapping(
            'rb',
            'Frb',
            'Radio Buttons',
            'rb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'self' => 'URL/rest/api/2/customFieldOption/10005',
                        'value' => 'test',
                        'id' => '10005'
                    ],
                    null
                )
            ],
            null
        );



        $this->exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame("10005", (string) $field_change_node->value[0]);
    }

    public function testItExportsTheSelectedValuesInAMultiSelectboxField(): void
    {
        $mapping = new FieldMapping(
            'msb',
            'Fmsb',
            'Multi Select Box',
            'msb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        [
                            'self' => 'URL/rest/api/2/customFieldOption/10009',
                            'value' => 'multi1',
                            'id' => '10009'
                        ],
                        [
                            'self' => 'URL/rest/api/2/customFieldOption/10010',
                            'value' => 'multi2',
                            'id' => '10010'
                        ]

                    ],
                    null
                )
            ],
            null
        );
        $this->exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(2, $field_change_node->value);
        $this->assertSame("10009", (string) $field_change_node->value[0]);
        $this->assertSame("10010", (string) $field_change_node->value[1]);
    }

    public function testItExportsTheStatusValuesInASelectboxFieldWithTransformedIDs(): void
    {
        $mapping = new FieldMapping(
            'status',
            'Fstatus',
            'status',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
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
                        'id' => '10001',
                        'statusCategory' =>
                            [
                                'self' => 'URL/rest/api/2/statuscategory/3',
                                'id' => 3,
                                'key' => 'done',
                                'colorName' => 'green',
                                'name' => 'Done'
                            ]
                    ],
                    null
                )
            ],
            null
        );
        $this->exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame("9010001", (string) $field_change_node->value);
    }

    public function testItExportsTheUsersInASelectboxField(): void
    {
        $mapping = new FieldMapping(
            'assignee',
            'Fassignee',
            'assignee',
            'sb',
            \Tracker_FormElement_Field_List_Bind_Users::TYPE
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [
                        'id' => '105'
                    ],
                    null
                )
            ],
            null
        );

        $john_doe = Mockery::mock(PFUser::class);
        $john_doe->shouldReceive('getLdapId')->andReturn(105);
        $john_doe->shouldReceive('getId')->andReturn(105);
        $this->user_manager->shouldReceive('getUserById')->andReturn($john_doe);
        $this->exporter->exportFieldChanges(
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
        $mapping = new FieldMapping(
            'homies',
            'Fhomies',
            'homies',
            'msb',
            \Tracker_FormElement_Field_List_Bind_Users::TYPE
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $snapshot = new Snapshot(
            Mockery::mock(PFUser::class),
            new \DateTimeImmutable(),
            [
                new FieldSnapshot(
                    $mapping,
                    [

                        ['id' => '105'],
                        ['id' => '106']
                    ],
                    null
                )
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
        $this->exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame('list', (string) $field_change_node['type']);
        $this->assertCount(2, $field_change_node->value);
        $this->assertSame('105', (string) $field_change_node->value[0]);
        $this->assertSame('106', (string) $field_change_node->value[1]);
    }
}
