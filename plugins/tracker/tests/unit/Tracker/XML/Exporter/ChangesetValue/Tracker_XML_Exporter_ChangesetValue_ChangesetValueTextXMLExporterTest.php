<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_File */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    protected function setUp(): void
    {
        $this->field         = Mockery::spy(Tracker_FormElement_Field_File::class)->shouldReceive('getName')->andReturn('textarea')->getMock();
        $this->exporter      = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter(
            new FieldChangeTextBuilder(
                new XML_SimpleXMLCDATAFactory()
            )
        );

        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->changeset_value->shouldReceive('getField')->andReturns($this->field);
    }

    public function testItCreatesTextNodeWithHTMLFormattedText(): void
    {
        $this->changeset_value->shouldReceive('getText')->andReturns('<p>test</p>');
        $this->changeset_value->shouldReceive('getFormat')->andReturns(Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEquals('textarea', (string) $field_change['field_name']);
        $this->assertEquals('text', (string) $field_change['type']);

        $this->assertEquals('<p>test</p>', (string) $field_change->value);
        $this->assertEquals('html', (string) $field_change->value['format']);
    }
}
