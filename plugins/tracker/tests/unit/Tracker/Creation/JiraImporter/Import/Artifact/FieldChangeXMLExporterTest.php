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
            )
        );
    }

    public function testItExportsTheUpdateDateAsSubmittedOnDateInXML(): void
    {
        $mapping = new FieldMapping(
            'update',
            'Fupdate',
            'Updated',
            'lud'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $submitted_on = new SimpleXMLElement('<submitted_on/>');
        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            '2020-04-21T09:31:44.481+0200',
            'mercredi 4:45 PM'
        );

        $this->assertSame("2020-04-21T09:31:44.481+0200", (string) $submitted_on);
    }

    public function testItDoesNotUpdateTheSubmissionDateWhenUpdatedDataIsNotProvided(): void
    {
        $mapping = new FieldMapping(
            'number',
            'Fnumber',
            'Number',
            'float'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $submitted_on = new SimpleXMLElement('<submitted_on format="ISO8601">2020-04-29T08:45:46+02:00</submitted_on>');

        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            '4.5',
            '4.5'
        );

        $this->assertSame("2020-04-29T08:45:46+02:00", (string) $submitted_on);
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
        $submitted_on = new SimpleXMLElement('<submitted_on/>');
        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            "h1. Coin\r\n\r\nLorem *ipsum* _doloret_ plop.",
            "<h1><a name=\"Coin\"></a>Coin</h1>\n\n<p>Lorem <b>ipsum</b> <em>doloret</em> plop.</p>"
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
        $submitted_on = new SimpleXMLElement('<submitted_on/>');
        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            [
                'self' => 'URL/rest/api/2/priority/3',
                'iconUrl' => 'URL/images/icons/priorities/medium.svg',
                'name' => 'Medium',
                'id' => '3',
            ],
            null
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
        $submitted_on = new SimpleXMLElement('<submitted_on/>');
        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            [
                'self' => 'URL/rest/api/2/customFieldOption/10005',
                'value' => 'test',
                'id' => '10005'
            ],
            null
        );

        $field_change_node = $changeset_node->field_change;
        $this->assertSame("list", (string) $field_change_node['type']);
        $this->assertCount(1, $field_change_node->value);
        $this->assertSame("10005", (string) $field_change_node->value[0]);
    }
}
