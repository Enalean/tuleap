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

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesTransformer;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class FieldChangeXMLExporterTest extends TestCase
{
    /**
     * @var FieldChangeXMLExporter
     */
    private $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new FieldChangeXMLExporter(
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
            new StatusValuesTransformer()
        );
    }

    public function testItExportsTheRenderedValueOfTextFieldsAsHTMLFormat(): void
    {
        $mapping = new FieldMapping(
            'description',
            'Fdescription',
            'Description',
            'text'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $this->exporter->exportFieldChanges(
            [
                [
                    "mapping" => $mapping,
                    "value"  => "h1. Coin\r\n\r\nLorem *ipsum* _doloret_ plop.",
                    "rendered_value" => "<h1><a name=\"Coin\"></a>Coin</h1>\n\n<p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>"
                ]
            ],
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
            'sb'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $this->exporter->exportFieldChanges(
            [
                [
                    "mapping" => $mapping,
                    "value"  => [
                        'self' => 'URL/rest/api/2/priority/3',
                        'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                        'name' => 'Medium',
                        'id' => '3',
                    ],
                    "rendered_value" => null
                ]
            ],
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
            'rb'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $this->exporter->exportFieldChanges(
            [
                [
                    "mapping" => $mapping,
                    "value"  => [
                        'self' => 'URL/rest/api/2/customFieldOption/10005',
                        'value' => 'test',
                        'id' => '10005'
                    ],
                    "rendered_value" => null
                ]
            ],
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
            'msb'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $this->exporter->exportFieldChanges(
            [
                [
                    "mapping" => $mapping,
                    "value"  => [
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
                    "rendered_value" => null
                ]
            ],
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
            'sb'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $this->exporter->exportFieldChanges(
            [
                [
                    "mapping" => $mapping,
                    "value"  => [
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
                    "rendered_value" => null
                ]
            ],
            $changeset_node
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame("9010001", (string) $field_change_node->value);
    }
}
