<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project\XML\Import;

use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;

final class ExternalFieldsExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EventManager&MockObject $event_manager;
    private ExternalFieldsExtractor $external_field_extractor;

    public function setUp(): void
    {
        $this->event_manager            = $this->createMock(EventManager::class);
        $this->external_field_extractor = new ExternalFieldsExtractor($this->event_manager);
    }

    public function testItExtractOneExternalField(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name><![CDATA[ShortName]]></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="0">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                        <item ID="V2065" label="Code review+" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2064"/>
                                    </default_values>
                                </bind>
                            </formElement>
                                <externalField type="ttmstepdef" ID="F1602" rank="2">
                                     <name>steps</name>
                                     <label><![CDATA[Steps definition]]></label>
                                     <description><![CDATA[Definition of the test\'s steps]]></description>
                                </externalField>
                        </formElements>
                          <permissions>
                             <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                             <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                             <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                           </permissions>
                    </tracker>'
        );

        $this->event_manager->expects(self::once())->method('processEvent');
        $this->external_field_extractor->extractExternalFieldsFromTracker($xml_input);
        self::assertEquals([], $xml_input->xpath('externalField'));
        self::assertEquals([], $xml_input->xpath('permission'));
    }

    public function testItExtractExternalFieldWithChangeset(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name><![CDATA[ShortName]]></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="0">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                        <item ID="V2065" label="Code review+" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2064"/>
                                    </default_values>
                                </bind>
                            </formElement>
                                <externalField type="ttmstepdef" ID="F1602" rank="2">
                                     <name>steps</name>
                                     <label><![CDATA[Steps definition]]></label>
                                     <description><![CDATA[Definition of the test\'s steps]]></description>
                                </externalField>
                        </formElements>
                          <permissions>
                             <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                             <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                             <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                           </permissions>
                        <artifacts>
                            <artifact id="1916">
                                <changeset>
                                    <submitted_by format="ldap">103</submitted_by>
                                    <submitted_on format="ISO8601">2020-03-05T13:42:01+01:00</submitted_on>
                                    <comments/>
                                    <field_change field_name="summary" type="string">
                                      <value><![CDATA[Ceci n\'est pas un test]]></value>
                                    </field_change>
                                    <field_change field_name="details" type="text">
                                      <value format="text"><![CDATA[]]></value>
                                    </field_change>
                                    <external_field_change field_name="steps" type="steps">
                                        <description_format><![CDATA[text]]></description_format>
                                        <description><![CDATA[Yep]]></description>
                                        <expected_results_format><![CDATA[text]]></expected_results_format>
                                        <expected_results><![CDATA[Non]]></expected_results>
                                    </external_field_change>
                                    <field_change field_name="automated_tests" type="string">
                                      <value><![CDATA[]]></value>
                                    </field_change>
                                </changeset>
                            </artifact>
                        </artifacts>
                    </tracker>'
        );

        $this->event_manager->expects(self::exactly(2))->method('processEvent');
        $this->external_field_extractor->extractExternalFieldsFromTracker($xml_input);
        self::assertEquals([], $xml_input->xpath('externalField'));
        self::assertEquals([], $xml_input->xpath('permission'));
        self::assertEquals([], $xml_input->xpath('external_field_change'));
    }

    public function testItExtractMultipleExternalField(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name><![CDATA[ShortName]]></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                        <formElements>
                            <formElement type="fieldset" ID="F1685" rank="4" required="0">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <description><![CDATA[One line description of the artifact]]></description>
                                <formElements>
                                    <formElement type="sb" ID="F1695" rank="7" required="0">
                                    <name>status</name>
                                    <label><![CDATA[Status]]></label>
                                    <bind type="static" is_rank_alpha="0">
                                        <items>
                                            <item ID="V2067" label="Code review" is_hidden="0"/>
                                            <item ID="V2068" label="Code review+" is_hidden="0"/>
                                        </items>
                                        <default_values>
                                            <value REF="V2067"/>
                                        </default_values>
                                    </bind>
                                    </formElement>
                                    <externalField type="ttmstepdef" ID="F1612" rank="2">
                                         <name>steps</name>
                                         <label><![CDATA[Steps definition]]></label>
                                         <description><![CDATA[Definition of the test\'s steps]]></description>
                                    </externalField>
                                </formElements>
                            </formElement>
                                <externalField type="ttmstepdef" ID="F1602" rank="2">
                                     <name>steps</name>
                                     <label><![CDATA[Steps definition]]></label>
                                     <description><![CDATA[Definition of the test\'s steps]]></description>
                                </externalField>
                        </formElements>
                        <permissions>
                            <permission scope="field" REF="F1612" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                            <permission scope="field" REF="F1612" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                            <permission scope="field" REF="F1612" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                            <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                            <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                            <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                        </permissions>
                    </tracker>'
        );

        $this->event_manager->expects(self::exactly(2))->method('processEvent');
        $this->external_field_extractor->extractExternalFieldsFromTracker($xml_input);
        self::assertEquals([], $xml_input->xpath('externalField'));
        self::assertEquals([], $xml_input->xpath('permission'));
    }

    public function testItExtractMultipleExternalFieldFromProjectElement(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                    <project unix-name="test-tracker-semantics" full-name="testTrackerSemantics" description="For test" access="public">
                        <long-description>Semantics</long-description>
                        <services>
                        </services>
                        <ugroups>
                            <ugroup name="project_members" description="">
                                <members>
                                    <member format="username">rest_api_tester_1</member>
                                </members>
                            </ugroup>
                            <ugroup name="project_admins" description="">
                                <members>
                                    <member format="username">rest_api_tester_1</member>
                                </members>
                            </ugroup>
                        </ugroups>
                       <trackers>
                        <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                            <name><![CDATA[Name]]></name>
                            <item_name><![CDATA[ShortName]]></item_name>
                            <description><![CDATA[Description]]></description>
                            <cannedResponses/>
                            <formElements>
                                <formElement type="fieldset" ID="F1685" rank="4" required="0">
                                    <name>status</name>
                                    <label><![CDATA[Status]]></label>
                                    <description><![CDATA[One line description of the artifact]]></description>
                                    <formElements>
                                        <formElement type="sb" ID="F1695" rank="7" required="0">
                                        <name>status</name>
                                        <label><![CDATA[Status]]></label>
                                        <bind type="static" is_rank_alpha="0">
                                            <items>
                                                <item ID="V2067" label="Code review" is_hidden="0"/>
                                                <item ID="V2068" label="Code review+" is_hidden="0"/>
                                            </items>
                                            <default_values>
                                                <value REF="V2067"/>
                                            </default_values>
                                        </bind>
                                        </formElement>
                                        <externalField type="ttmstepdef" ID="F1612" rank="2">
                                             <name>steps</name>
                                             <label><![CDATA[Steps definition]]></label>
                                             <description><![CDATA[Definition of the test\'s steps]]></description>
                                        </externalField>
                                    </formElements>
                                </formElement>
                            </formElements>
                            <permissions>
                                <permission scope="field" REF="F1612" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                                <permission scope="field" REF="F1612" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                                <permission scope="field" REF="F1612" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                            </permissions>
                        </tracker>
                        <tracker id="T102" parent_id="0" instantiate_for_new_projects="1">
                            <name><![CDATA[Name]]></name>
                            <item_name><![CDATA[ShortName]]></item_name>
                            <description><![CDATA[Description]]></description>
                            <cannedResponses/>
                            <formElements>
                                <formElement type="fieldset" ID="F1685" rank="4" required="0">
                                    <name>status</name>
                                    <label><![CDATA[Status]]></label>
                                    <description><![CDATA[One line description of the artifact]]></description>
                                </formElement>
                                    <externalField type="ttmstepdef" ID="F1602" rank="2">
                                         <name>steps</name>
                                         <label><![CDATA[Steps definition]]></label>
                                         <description><![CDATA[Definition of the test\'s steps]]></description>
                                    </externalField>
                            </formElements>
                            <permissions>
                                <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                                <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                                <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                            </permissions>
                        </tracker>
                    </trackers>
                </project>'
        );

        $this->event_manager->expects(self::exactly(2))->method('processEvent');
        $this->external_field_extractor->extractExternalFieldFromProjectElement($xml_input);
        self::assertEquals([], $xml_input->xpath('externalField'));
        self::assertEquals([], $xml_input->xpath('permission'));
    }
}
