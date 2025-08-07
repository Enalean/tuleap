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
 */

namespace Tuleap\TestManagement\XML;

use SimpleXMLElement;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ImportXMLFromTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ImportXMLFromTracker
     */
    private $xml_validator;

    public function setUp(): void
    {
        $this->xml_validator = new ImportXMLFromTracker(new XML_RNGValidator());
    }

    public function testValidateXMLImportThrowExceptionIfNotValidXML(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepdef" ID="F1602" rank="2" id="1602" tracker_id="53" parent_id="0">
                  <name>steps</name>
                  <description><![CDATA[Definition of the test\'s steps]]></description>
                 <permissions>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                 </permissions>
             </externalField>'
        );

        $this->expectException('XML_ParseException');
        $this->xml_validator->validateXMLImport($xml_input);
    }

    public function testValidateXMLImportNotThrowException(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepdef" ID="F1602" rank="2">
	            <name>steps</name>
	            <label>Steps definition</label>
	            <description>Definition</description>
            </externalField>'
        );

        $this->xml_validator->validateXMLImport($xml_input);
        $this->addToAssertionCount(1);
    }

    public function testValidateChangesetXMLImportThrowExceptionIfNotValidXML(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <external_field_change field_name="steps" type="ttmstepdef">
                      <description><![CDATA[Yep]]></description>
                      <expected_results_format><![CDATA[text]]></expected_results_format>
                      <expected_results><![CDATA[Non]]></expected_results>
                  </external_field_change>'
        );

        $this->expectException('XML_ParseException');
        $this->xml_validator->validateChangesetXMLImport($xml_input);
    }

    public function testValidateChangesetXMLImportWithMutlipleStepNotThrowException(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <external_field_change field_name="steps" type="ttmstepdef">
                    <step>
                      <description format="html"><![CDATA[Yep]]></description>
                      <expected_results format="html"><![CDATA[Non]]></expected_results>
                    </step>
                    <step>
                      <description format="text"><![CDATA[Yep]]></description>
                      <expected_results format="text"><![CDATA[Non]]></expected_results>
                    </step>
                  </external_field_change>'
        );

        $this->xml_validator->validateChangesetXMLImport($xml_input);
        $this->addToAssertionCount(1);
    }

    public function testValidateChangesetXMLImportThrowsExceptionIfNoStepsOnExternalChangeset(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <external_field_change field_name="steps" type="ttmstepdef">
                  </external_field_change>'
        );

        $this->expectException('XML_ParseException');
        $this->xml_validator->validateChangesetXMLImport($xml_input);
    }

    public function testGetInstanceFromXMLReturnsStepDefinition()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepdef" ID="F1602" rank="2">
                  <name>steps</name>
                  <label>Steps definition</label>
                  <description>Definition of the test\'s steps</description>
             </externalField>'
        );

        $step_def = new StepsDefinition(
            0,
            0,
            0,
            'steps',
            'Steps definition',
            "Definition of the test's steps",
            1,
            'P',
            0,
            0,
            2,
            null
        );

        $result = $this->xml_validator->getInstanceFromXML($xml_input);

        $this->assertEquals($step_def, $result);
    }

    public function testGetInstanceFromXMLReturnsStepExecution()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepexec" ID="F1602" rank="2">
                  <name>steps</name>
                  <label>Steps Execution</label>
                  <description>Execution of the test\'s steps</description>
             </externalField>'
        );

        $step_def = new StepExecution(
            0,
            0,
            0,
            'steps',
            'Steps Execution',
            "Execution of the test's steps",
            1,
            'P',
            0,
            0,
            2,
            null
        );

        $result = $this->xml_validator->getInstanceFromXML($xml_input);

        $this->assertEquals($step_def, $result);
    }

    public function testGetInstanceFromXMLReturnsNullIfWrongType()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="notttmstepexec" ID="F1602" rank="2">
                  <name>steps</name>
                  <label>Steps Execution</label>
                  <description>Execution of the test\'s steps</description>
             </externalField>'
        );

        $this->assertNull($this->xml_validator->getInstanceFromXML($xml_input));
    }
}
