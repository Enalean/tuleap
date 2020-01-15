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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use XML_RNGValidator;

final class TrackerXMLImportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
             <externalFields>
                <testmanagementStepDef type="ttmstepdef" ID="F1602" rank="2">
                  <name>steps</name>
                  <description><![CDATA[Definition of the test\'s steps]]></description>
                </testmanagementStepDef>
                 <permissions>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                 </permissions>
             </externalFields>'
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
	            <permissions>
	                <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
   	                <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
	            </permissions>
            </externalField>'
        );

        $this->xml_validator->validateXMLImport($xml_input);
        $this->addToAssertionCount(1);
    }
}
