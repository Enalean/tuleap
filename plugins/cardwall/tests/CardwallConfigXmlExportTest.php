<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

require_once __DIR__ .'/bootstrap.php';

class CardwallConfigXmlExportTest extends TuleapTestCase
{

    /** @var CardwallConfigXmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project  = mockery_stub(\Project::class)->getID()->returns(140);
        $this->tracker1 = aTracker()->withId(214)->build();
        $this->tracker2 = aTracker()->withId(614)->build();
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config  = mockery_stub(\Cardwall_OnTop_Config::class)->isEnabled()->returns(false);
        $this->cardwall_config2 = mockery_stub(\Cardwall_OnTop_Config::class)->isEnabled()->returns(true);

        $this->tracker_factory = mockery_stub(\TrackerFactory::class)->getTrackersByGroupId(140)->returns(
            array(214 => $this->tracker1, 614 => $this->tracker2)
        );

        $this->config_factory = \Mockery::spy(\Cardwall_OnTop_ConfigFactory::class);
        $this->xml_validator  = \Mockery::spy(\XML_RNGValidator::class);

        $this->xml_exporter = new CardwallConfigXmlExport(
            $this->project,
            $this->tracker_factory,
            $this->config_factory,
            $this->xml_validator
        );
    }

    public function itReturnsTheGoodRootXmlWithTrackers()
    {
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker1)->once()->andReturn($this->cardwall_config);
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker2)->once()->andReturn($this->cardwall_config2);

        $this->cardwall_config2->shouldReceive('getDashboardColumns')->andReturn([]);

        $this->xml_exporter->export($this->root);
        $attributes = $this->root->cardwall->trackers->tracker->attributes();
        $this->assertEqual(count($this->root->cardwall->trackers->children()), 1);
        $this->assertEqual((String) $attributes['id'], 'T614');
    }

    public function itReturnsTheGoodRootXmlWithoutTrackers()
    {
        $cardwall_config       = mockery_stub(\Cardwall_OnTop_Config::class)->isEnabled()->returns(false);
        $cardwall_config2      = mockery_stub(\Cardwall_OnTop_Config::class)->isEnabled()->returns(false);
        $this->config_factory2 = \Mockery::spy(\Cardwall_OnTop_ConfigFactory::class);

        stub($this->config_factory2)->getOnTopConfig($this->tracker1)->returns($cardwall_config);
        stub($this->config_factory2)->getOnTopConfig($this->tracker2)->returns($cardwall_config2);

        $xml_exporter2 = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory2, $this->xml_validator);

        $xml_exporter2->export($this->root);
        $this->assertEqual(count($this->root->cardwall->trackers->children()), 0);
    }

    public function itThrowsAnExceptionIfXmlGeneratedIsNotValid()
    {
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker1)->once()->andReturn($this->cardwall_config);
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker2)->once()->andReturn($this->cardwall_config2);

        $this->cardwall_config2->shouldReceive('getDashboardColumns')->andReturn([]);

        $this->expectException();

        $xml_validator = mockery_stub(\XML_RNGValidator::class)->validate()->throws(new XML_ParseException('', array(), array()));
        $xml_exporter  = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $xml_validator);
        $xml_exporter->export(new SimpleXMLElement('<empty/>'));
    }
}

class CardwallConfigXmlExport_ColumnsTest extends TuleapTestCase
{

    /** @var CardwallConfigXmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project  = mockery_stub(\Project::class)->getID()->returns(140);
        $this->tracker1 = aTracker()->withId(214)->build();
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config = \Mockery::spy(\Cardwall_OnTop_Config::class);
        stub($this->cardwall_config)->isEnabled()->returns(true);

        $this->tracker_factory = mockery_stub(\TrackerFactory::class)->getTrackersByGroupId(140)->returns(array(214 => $this->tracker1));

        $this->config_factory = \Mockery::spy(\Cardwall_OnTop_ConfigFactory::class);
        stub($this->config_factory)->getOnTopConfig($this->tracker1)->returns($this->cardwall_config);

        $this->xml_validator = \Mockery::spy(\XML_RNGValidator::class);

        $this->xml_exporter = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $this->xml_validator);
    }

    public function itDumpsNoColumnsWhenNoColumnsDefined()
    {
        stub($this->cardwall_config)->getDashboardColumns()->returns(new Cardwall_OnTop_Config_ColumnCollection(array()));
        stub($this->cardwall_config)->getMappings()->returns(array());

        $this->xml_exporter->export($this->root);
        $this->assertEqual(count($this->root->cardwall->trackers->tracker->children()), 0);
    }

    public function itDumpsColumnsAsDefined()
    {
        stub($this->cardwall_config)->getDashboardColumns()->returns(new Cardwall_OnTop_Config_ColumnCollection(array(
            new Cardwall_Column(112, "Todo", "red"),
            new Cardwall_Column(113, "On going", "fiesta-red"),
            new Cardwall_Column(113, "On going", "rgb(255,255,255)")
        )));

        stub($this->cardwall_config)->getMappings()->returns(array());

        $this->xml_exporter->export($this->root);
        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;

        $this->assertCount($column_xml, 3);
    }

    public function itDumpsColumnsAsDefinedWithMappings()
    {
        stub($this->cardwall_config)->getDashboardColumns()->returns(new Cardwall_OnTop_Config_ColumnCollection(array(
            new Cardwall_Column(112, "Todo", "red"),
            new Cardwall_Column(113, "On going", "fiesta-red"),
            new Cardwall_Column(113, "On going", "rgb(255,255,255)")
        )));

        $tracker = mockery_stub(\Tracker::class)->getXMLId()->returns('T200');
        $field   = mockery_stub(\Tracker_FormElement_Field_List::class)->getXMLId()->returns('F201');

        $value_mapping = \Mockery::spy(\Cardwall_OnTop_Config_ValueMapping::class);
        stub($value_mapping)->getXMLValueId()->returns('V304');
        stub($value_mapping)->getColumnId()->returns(4);

        $mapping = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMappingFreestyle::class);
        stub($mapping)->getTracker()->returns($tracker);
        stub($mapping)->getField()->returns($field);
        stub($mapping)->getValueMappings()->returns(array($value_mapping));
        stub($mapping)->isCustom()->returns(true);

        stub($this->cardwall_config)->getMappings()->returns(array($mapping));

        $this->xml_exporter->export($this->root);

        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;
        $this->assertCount($column_xml, 3);

        $mapping_xml = $this->root->cardwall->trackers->tracker->mappings->mapping;
        $this->assertCount($mapping_xml, 1);
        $this->assertEqual($mapping_xml['tracker_id'], ('T200'));
        $this->assertEqual($mapping_xml['field_id'], ('F201'));

        $mapping_values_xml = $this->root->cardwall->trackers->tracker->mappings->mapping->values->value;
        $this->assertCount($mapping_values_xml, 1);
        $this->assertEqual($mapping_values_xml['value_id'], ('V304'));
        $this->assertEqual($mapping_values_xml['column_id'], ('C4'));
    }
}
