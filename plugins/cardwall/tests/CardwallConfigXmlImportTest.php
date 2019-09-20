<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

require_once dirname(__FILE__) .'/bootstrap.php';

class CardwallConfigXmlImportTest extends TuleapTestCase
{

    private $default_xml_input;
    private $enhanced_xml_input;
    /**
     * @var a|\Mockery\MockInterface|EventManager
     */
    private $event_manager;

    public function setUp()
    {
        parent::setUp();
        $this->default_xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
              <trackers>
                  <tracker xmlns="http://codendi.org/tracker" id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>t10</name>
                    <item_name>t11</item_name>
                    <description>t12</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t20</name>
                    <item_name>t21</item_name>
                    <description>t22</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>
              </trackers>
              <cardwall>
                <trackers>
                    <tracker id="T101"/>
                    <tracker id="T102">
                        <columns>
                            <column label="Todo"/>
                            <column label="On going"/>
                            <column label="Review"/>
                            <column label="Done"/>
                        </columns>
                    </tracker>
                </trackers>
              </cardwall>
              <agiledashboard/>
            </project>');

        $this->enhanced_xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
              <trackers>
                  <tracker xmlns="http://codendi.org/tracker" id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>t10</name>
                    <item_name>t11</item_name>
                    <description>t12</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t20</name>
                    <item_name>t21</item_name>
                    <description>t22</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                        <formElements>
                            <formElement type="sb" ID="F1" rank="0">
                                <name>progress</name>
                                <label><![CDATA[Progress]]></label>
                                <bind type="static" is_rank_alpha="0">
                                  <items>
                                    <item ID="V1" label="To do" is_hidden="0"/>
                                    <item ID="V2" label="On going" is_hidden="0"/>
                                    <item ID="V3" label="Review" is_hidden="0"/>
                                    <item ID="V4" label="Done" is_hidden="0"/>
                                  </items>
                                  <default_values>
                                    <value REF="V320"/>
                                  </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                  </tracker>
              </trackers>
              <cardwall>
                <trackers>
                    <tracker id="T101"/>
                    <tracker id="T102">
                        <columns>
                            <column id="C1" label="Todo"/>
                            <column id="C2" label="On going" bg_red="255" bg_green="255" bg_blue="240"/>
                            <column id="C3" label="Review"/>
                            <column id="C4" label="Done" tlp_color_name="fiesta-red"/>
                        </columns>
                        <mappings>
                            <mapping tracker_id="T103" field_id="F1">
                                <values>
                                    <value value_id="V1" column_id="C1"/>
                                    <value value_id="V4" column_id="C3"/>
                                    <value value_id="" column_id="C4"/>
                                </values>
                            </mapping>
                        </mappings>
                    </tracker>
                </trackers>
              </cardwall>
              <agiledashboard/>
            </project>');

        $field = stub('Tracker_FormElement_Field_List')->getId()->returns(1);
        $value_01 = stub('Tracker_Artifact_ChangesetValue_List')->getId()->returns(401);
        $value_02 = stub('Tracker_Artifact_ChangesetValue_List')->getId()->returns(402);
        $value_03 = stub('Tracker_Artifact_ChangesetValue_List')->getId()->returns(403);
        $value_04 = stub('Tracker_Artifact_ChangesetValue_List')->getId()->returns(404);

        $this->mapping = array(
            "T101" => 444,
            "T102" => 555,
            "T103" => 666
        );

        $this->field_mapping = array(
            "F1" => $field,
            "V1" => $value_01,
            "V2" => $value_02,
            "V3" => $value_03,
            "V4" => $value_04
        );

        $this->cardwall_ontop_dao         = stub('Cardwall_OnTop_Dao')->enable()->returns(true);
        $this->column_dao                 = mock('Cardwall_OnTop_ColumnDao');
        $this->mapping_field_dao          = mock('Cardwall_OnTop_ColumnMappingFieldDao');
        $this->mapping_field_value_dao    = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');
        $this->group_id                   = 145;
        $this->event_manager              = \Mockery::mock(\EventManager::class);
        $this->xml_validator              = mock('XML_RNGValidator');
        $this->cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->xml_validator
        );
    }

    public function itStoresAllTheCardwallOnTop()
    {
        $this->event_manager->shouldReceive('processEvent');

        expect($this->cardwall_ontop_dao)->enable()->count(2);
        expect($this->cardwall_ontop_dao)->enableFreestyleColumns()->count(2);

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function itCreatesTheFreestyleColumns()
    {
        $this->event_manager->shouldReceive('processEvent');

        expect($this->column_dao)->createWithcolor()->count(4);
        expect($this->column_dao)->createWithcolor(555, 'Todo', '', '', '')->at(0);
        expect($this->column_dao)->createWithcolor(555, 'On going', '', '', '')->at(1);
        expect($this->column_dao)->createWithcolor(555, 'Review', '', '', '')->at(2);
        expect($this->column_dao)->createWithcolor(555, 'Done', '', '', '')->at(3);

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function itCreatesTheFreestyleColumnsWithColor()
    {
        $this->event_manager->shouldReceive('processEvent');

        stub($this->column_dao)->createWithcolor()->returnsAt(0, 20);
        stub($this->column_dao)->createWithcolor()->returnsAt(1, 21);
        stub($this->column_dao)->createWithcolor()->returnsAt(2, 22);
        stub($this->column_dao)->createWithTLPColor();

        expect($this->column_dao)->createWithcolor()->count(3);
        expect($this->column_dao)->createWithTLPColor()->count(1);

        expect($this->column_dao)->createWithcolor(555, 'Todo', '', '', '')->at(0);
        expect($this->column_dao)->createWithcolor(555, 'On going', 255, 255, 240)->at(1);
        expect($this->column_dao)->createWithcolor(555, 'Review', '', '', '')->at(2);

        expect($this->column_dao)->createWithTLPColor(555, 'Done', 'fiesta-red');

        $this->cardwall_config_xml_import->import($this->enhanced_xml_input);
    }

    public function itDoesNotCreateMappingAndMappingValueinDefaultXML()
    {
        $this->event_manager->shouldReceive('processEvent');

        expect($this->mapping_field_dao)->create()->never();
        expect($this->mapping_field_value_dao)->save()->never();

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function itCreatesMappingAndMappingValue()
    {
        $this->event_manager->shouldReceive('processEvent');

        stub($this->column_dao)->createWithcolor()->returnsAt(0, 20);
        stub($this->column_dao)->createWithcolor()->returnsAt(1, 21);
        stub($this->column_dao)->createWithcolor()->returnsAt(2, 22);
        stub($this->column_dao)->createWithTLPColor()->returnsAt(0, 23);

        expect($this->mapping_field_dao)->create()->count(1);
        expect($this->mapping_field_dao)->create(555, 666, 1)->at(0);

        expect($this->mapping_field_value_dao)->save()->count(3);
        expect($this->mapping_field_value_dao)->save(555, 666, 1, 401, '*')->at(0);
        expect($this->mapping_field_value_dao)->save(555, 666, 1, 404, '*')->at(1);
        expect($this->mapping_field_value_dao)->save(555, 666, 1, 100, '*')->at(2);

        $this->cardwall_config_xml_import->import($this->enhanced_xml_input);
    }

    public function itProcessesANewEventIfAllCardwallAreEnabled()
    {
        $this->event_manager->shouldReceive('processEvent')->with(
            Event::IMPORT_XML_PROJECT_CARDWALL_DONE,
            array(
                'project_id'  => $this->group_id,
                'xml_content' => $this->default_xml_input,
                'mapping'     => $this->mapping
            )
        );

        $this->cardwall_ontop_dao->expectCallCount('enable', 2);

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function itDoesNotProcessAnEventIfAtLeastOneCardwallCannotBeEnabledAndThrowsAnException()
    {
        $cardwall_ontop_dao         = stub('Cardwall_OnTop_Dao')->enable()->returns(false);
        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->xml_validator
        );

        $this->event_manager->shouldNotReceive('processEvent')->with(Event::IMPORT_XML_PROJECT_CARDWALL_DONE, \Mockery::any());

        $this->expectException();
        $cardwall_ontop_dao->expectCallCount('enable', 1);

        $cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function itThrowsAnExceptionIfXmlDoesNotMatchRNG()
    {
        $xml_validator  = stub('XML_RNGValidator')->validate()->throws(new XML_ParseException('', array(), array()));

        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $xml_validator
        );

        $this->expectException('XML_ParseException');

        $cardwall_config_xml_import->import($this->default_xml_input);
    }
}
