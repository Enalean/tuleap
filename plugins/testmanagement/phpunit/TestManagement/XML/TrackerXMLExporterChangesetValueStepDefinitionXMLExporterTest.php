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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\TestManagement\Step\Step;
use XML_SimpleXMLCDATAFactory;

final class TrackerXMLExporterChangesetValueStepDefinitionXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsTheExternalChangeset(): void
    {
        $exporter     = new TrackerXMLExporterChangesetValueStepDefinitionXMLExporter(new XML_SimpleXMLCDATAFactory());
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
            'text',
            'nope',
            'text',
            2
        );

        $values = [$step1, $step2];

        $field = Mockery::mock(StepDefinition::class);
        $field->shouldReceive('getName')->andReturn('steps')->once();

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value->shouldReceive('getValue')->andReturn($values)->once();
        $changeset_value->shouldReceive('getField')->andReturn($field)->once();

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $exporter->export($artifact_xml, $changeset_xml, $artifact, $changeset_value);
        $this->assertEquals($this->getXmlResult(), $changeset_xml);
    }

    public function testItDoesntExportsTheExternalChangesetIfTheirIsNotStepOnChangeset(): void
    {
        $exporter     = new TrackerXMLExporterChangesetValueStepDefinitionXMLExporter(new XML_SimpleXMLCDATAFactory());
        $artifact_xml = new SimpleXMLElement('<artifact></artifact>');

        $xml_data = '<changeset>
            <submitted_by format="ldap">103</submitted_by>
            <submitted_on format="ISO8601">2020-03-05T13:42:01+01:00</submitted_on>
            </changeset>';

        $changeset_xml = new SimpleXMLElement($xml_data);
        $expected_xml = new SimpleXMLElement($xml_data);

        $values = [];

        $field = Mockery::mock(StepDefinition::class);
        $field->shouldReceive('getName')->andReturn('steps')->never();

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value->shouldReceive('getValue')->andReturn($values)->once();
        $changeset_value->shouldReceive('getField')->andReturn($field)->once();

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $exporter->export($artifact_xml, $changeset_xml, $artifact, $changeset_value);
        $this->assertEquals($expected_xml, $changeset_xml);
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
