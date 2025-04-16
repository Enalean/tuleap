<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerXMLExporterChangesetValueStepDefinitionXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsTheExternalChangeset(): void
    {
        $exporter     = new TrackerXMLExporterChangesetValueStepDefinitionXMLExporter(
            new StepXMLExporter(new XML_SimpleXMLCDATAFactory())
        );
        $artifact_xml = new SimpleXMLElement('<artifact></artifact>');

        $xml_data = '<changeset>
            <submitted_by format="ldap">103</submitted_by>
            <submitted_on format="ISO8601">2020-03-05T13:42:01+01:00</submitted_on>
            </changeset>';

        $changeset_xml = new SimpleXMLElement($xml_data);

        $step1 = new Step(
            1,
            'yep',
            'text',
            'nope',
            'text',
            1
        );
        $step2 = new Step(
            2,
            'yep',
            'html',
            'nope',
            'html',
            2
        );

        $values = [$step1, $step2];

        $field = $this->createMock(StepDefinition::class);
        $field->expects($this->once())->method('getName')->willReturn('steps');

        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value->expects($this->once())->method('getValue')->willReturn($values);
        $changeset_value->expects($this->once())->method('getField')->willReturn($field);

        $artifact = ArtifactTestBuilder::anArtifact(42)->build();
        $exporter->export($artifact_xml, $changeset_xml, $artifact, $changeset_value, []);
        $this->assertXmlStringEqualsXmlString($this->getXmlResult()->asXML(), $changeset_xml->asXML());
    }

    public function testItDoesntExportsTheExternalChangesetIfTheirIsNotStepOnChangeset(): void
    {
        $exporter     = new TrackerXMLExporterChangesetValueStepDefinitionXMLExporter(
            new StepXMLExporter(new XML_SimpleXMLCDATAFactory())
        );
        $artifact_xml = new SimpleXMLElement('<artifact></artifact>');

        $xml_data = '<changeset>
            <submitted_by format="ldap">103</submitted_by>
            <submitted_on format="ISO8601">2020-03-05T13:42:01+01:00</submitted_on>
            </changeset>';

        $changeset_xml = new SimpleXMLElement($xml_data);
        $expected_xml  = new SimpleXMLElement($xml_data);

        $values = [];

        $field = $this->createMock(StepDefinition::class);
        $field->expects($this->never())->method('getName');

        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value->expects($this->once())->method('getValue')->willReturn($values);
        $changeset_value->expects($this->once())->method('getField')->willReturn($field);

        $artifact = ArtifactTestBuilder::anArtifact(42)->build();
        $exporter->export($artifact_xml, $changeset_xml, $artifact, $changeset_value, []);
        $this->assertXmlStringEqualsXmlString($expected_xml->asXML(), $changeset_xml->asXML());
    }

    private function getXmlResult(): SimpleXMLElement
    {
        return new SimpleXMLElement('<changeset>
             <submitted_by format="ldap">103</submitted_by>
             <submitted_on format="ISO8601">2020-03-05T13:42:01+01:00</submitted_on>
             <external_field_change field_name="steps" type="ttmstepdef">
                <step>
                  <description format="text">yep</description>
                  <expected_results format="text">nope</expected_results>
                </step>
                <step>
                  <description format="html">yep</description>
                  <expected_results format="html">nope</expected_results>
                </step>
             </external_field_change>
            </changeset>');
    }
}
