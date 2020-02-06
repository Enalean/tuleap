<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class CardwallConfigXmlImportTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalResponseMock, \Tuleap\GlobalLanguageMock;

    private $default_xml_input;
    private $enhanced_xml_input;
    /**
     * @var \Mockery\MockInterface|EventManager
     */
    private $event_manager;
    /**
     * @var Cardwall_OnTop_Dao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cardwall_ontop_dao;
    /**
     * @var CardwallConfigXmlImport
     */
    private $cardwall_config_xml_import;

    protected function setUp() : void
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

        $field = \Mockery::spy(\Tracker_FormElement_Field_List::class)->shouldReceive('getId')->andReturns(1)->getMock();
        $value_01 = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getId')->andReturns(401)->getMock();
        $value_02 = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getId')->andReturns(402)->getMock();
        $value_03 = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getId')->andReturns(403)->getMock();
        $value_04 = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class)->shouldReceive('getId')->andReturns(404)->getMock();

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

        $this->cardwall_ontop_dao         = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $this->column_dao                 = \Mockery::spy(\Cardwall_OnTop_ColumnDao::class);
        $this->mapping_field_dao          = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->mapping_field_value_dao    = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->group_id                   = 145;
        $this->event_manager              = \Mockery::mock(\EventManager::class);
        $this->xml_validator              = \Mockery::spy(\XML_RNGValidator::class);
        $this->logger                     = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->artifact_id_mapping        = new Tracker_XML_Importer_ArtifactImportedMapping();

        $this->cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->xml_validator,
            $this->logger
        );
    }

    public function testItStoresAllTheCardwallOnTop() : void
    {
        $this->event_manager->shouldReceive('processEvent');

        $this->cardwall_ontop_dao->shouldReceive('enable')->times(2)->andReturn(true);
        $this->cardwall_ontop_dao->shouldReceive('enableFreestyleColumns')->times(2);

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItCreatesTheFreestyleColumns() : void
    {
        $this->event_manager->shouldReceive('processEvent');

        $this->cardwall_ontop_dao->shouldReceive('enable')->andReturn(true);

        $this->column_dao->shouldReceive('createWithcolor')->times(4);
        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'Todo', '', '', '')->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'On going', '', '', '')->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'Review', '', '', '')->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'Done', '', '', '')->ordered();

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItCreatesTheFreestyleColumnsWithColor() : void
    {
        $this->event_manager->shouldReceive('processEvent');

        $this->cardwall_ontop_dao->shouldReceive('enable')->andReturn(true);

        $this->column_dao->shouldReceive('createWithcolor')->andReturns(20)->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->andReturns(21)->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->andReturns(22)->ordered();
        $this->column_dao->shouldReceive('createWithTLPColor')->once();

        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'Todo', '', '', '')->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'On going', 255, 255, 240)->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->with(555, 'Review', '', '', '')->ordered();

        $this->column_dao->shouldReceive('createWithTLPColor')->with(555, 'Done', 'fiesta-red');

        $this->cardwall_config_xml_import->import($this->enhanced_xml_input);
    }

    public function testItDoesNotCreateMappingAndMappingValueinDefaultXML() : void
    {
        $this->event_manager->shouldReceive('processEvent');

        $this->cardwall_ontop_dao->shouldReceive('enable')->andReturn(true);

        $this->mapping_field_dao->shouldReceive('create')->never();
        $this->mapping_field_value_dao->shouldReceive('save')->never();

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItCreatesMappingAndMappingValue() : void
    {
        $this->event_manager->shouldReceive('processEvent');

        $this->cardwall_ontop_dao->shouldReceive('enable')->andReturn(true);

        $this->column_dao->shouldReceive('createWithcolor')->andReturns(20)->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->andReturns(21)->ordered();
        $this->column_dao->shouldReceive('createWithcolor')->andReturns(22)->ordered();
        $this->column_dao->shouldReceive('createWithTLPColor')->andReturns(23)->ordered();

        $this->mapping_field_dao->shouldReceive('create')->times(1);
        $this->mapping_field_dao->shouldReceive('create')->with(555, 666, 1)->ordered();

        $this->mapping_field_value_dao->shouldReceive('save')->times(3);
        $this->mapping_field_value_dao->shouldReceive('save')->with(555, 666, 1, 401, \Mockery::any())->ordered();
        $this->mapping_field_value_dao->shouldReceive('save')->with(555, 666, 1, 404, \Mockery::any())->ordered();
        $this->mapping_field_value_dao->shouldReceive('save')->with(555, 666, 1, 100, \Mockery::any())->ordered();

        $this->cardwall_config_xml_import->import($this->enhanced_xml_input);
    }

    public function testItProcessesANewEventIfAllCardwallAreEnabled() : void
    {
        $this->event_manager->shouldReceive('processEvent')->with(
            Event::IMPORT_XML_PROJECT_CARDWALL_DONE,
            array(
                'project_id'          => $this->group_id,
                'xml_content'         => $this->default_xml_input,
                'mapping'             => $this->mapping,
                'logger'              => $this->logger,
                'artifact_id_mapping' => $this->artifact_id_mapping
            )
        );

        $this->cardwall_ontop_dao->shouldReceive('enable')->times(2)->andReturn(true);

        $this->cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItDoesNotProcessAnEventIfAtLeastOneCardwallCannotBeEnabledAndThrowsAnException() : void
    {
        $cardwall_ontop_dao         = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $cardwall_ontop_dao->shouldReceive('enable')->andReturns(false)->once();
        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $this->xml_validator,
            $this->logger
        );

        $this->event_manager->shouldNotReceive('processEvent')->with(Event::IMPORT_XML_PROJECT_CARDWALL_DONE, \Mockery::any());

        $this->expectException(CardwallFromXmlImportCannotBeEnabledException::class);
        $cardwall_config_xml_import->import($this->default_xml_input);
    }

    public function testItThrowsAnExceptionIfXmlDoesNotMatchRNG() : void
    {
        $xml_validator  = \Mockery::spy(\XML_RNGValidator::class);
        $xml_validator->shouldReceive('validate')->andThrows(new XML_ParseException('', array(), array()));

        $cardwall_config_xml_import = new CardwallConfigXmlImport(
            $this->group_id,
            $this->mapping,
            $this->field_mapping,
            $this->artifact_id_mapping,
            $this->cardwall_ontop_dao,
            $this->column_dao,
            $this->mapping_field_dao,
            $this->mapping_field_value_dao,
            $this->event_manager,
            $xml_validator,
            $this->logger
        );

        $this->expectException(\XML_ParseException::class);

        $cardwall_config_xml_import->import($this->default_xml_input);
    }
}
