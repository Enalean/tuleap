<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
require_once 'common/XmlValidator/XmlValidator.class.php';

class CardwallConfigXmlExportTest extends TuleapTestCase {

    /** @var CardwallConfigXmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    public function setUp() {
        parent::setUp();

        $this->project  = stub('Project')->getId()->returns(140);
        $this->tracker1 = aTracker()->withId(214)->build();
        $this->tracker2 = aTracker()->withId(614)->build();
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config  = stub('Cardwall_OnTop_Config')->isEnabled()->returns(false);
        $this->cardwall_config2 = stub('Cardwall_OnTop_Config')->isEnabled()->returns(true);

        $this->tracker_factory = stub('TrackerFactory')->getTrackersByGroupId(140)->returns(array(214 => $this->tracker1, 614 => $this->tracker2));
        TrackerFactory::setInstance($this->tracker_factory);

        $this->config_factory = mock('Cardwall_OnTop_ConfigFactory');
        stub($this->config_factory)->getOnTopConfig($this->tracker1)->returns($this->cardwall_config);
        stub($this->config_factory)->getOnTopConfig($this->tracker2)->returns($this->cardwall_config2);

        $this->xml_validator = stub('XmlValidator')->nodeIsValid()->returns(true);

        $this->xml_exporter = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $this->xml_validator);
    }

    public function tearDown() {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    public function itReturnsTheGoodRootXmlWithTrackers() {
        $this->xml_exporter->export($this->root);
        $attributes = $this->root->cardwall->trackers->tracker->attributes();
        $this->assertEqual(count($this->root->cardwall->trackers->children()), 1);
        $this->assertEqual( (String) $attributes['id'], 'T614');
    }

     public function itReturnsTheGoodRootXmlWithoutTrackers() {
        $cardwall_config       = stub('Cardwall_OnTop_Config')->isEnabled()->returns(false);
        $cardwall_config2      = stub('Cardwall_OnTop_Config')->isEnabled()->returns(false);
        $this->config_factory2 = mock('Cardwall_OnTop_ConfigFactory');

        stub($this->config_factory2)->getOnTopConfig($this->tracker1)->returns($cardwall_config);
        stub($this->config_factory2)->getOnTopConfig($this->tracker2)->returns($cardwall_config2);

        $xml_exporter2 = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory2, $this->xml_validator);

        $xml_exporter2->export($this->root);
        $this->assertEqual(count($this->root->cardwall->trackers->children()), 0);
    }

    public function itCallsGetOnTopConfigMethodForEachTracker() {
        $this->config_factory->expectCallCount('getOnTopConfig', 2);
        $this->cardwall_config->expectCallCount('isEnabled', 1);
        $this->cardwall_config2->expectCallCount('isEnabled', 1);
        $this->xml_exporter->export($this->root);
    }

    public function itThrowsAnExceptionIfXmlGeneratedIsNotValid() {
        $this->expectException();

        $xml_validator = stub('XmlValidator')->nodeIsValid()->returns(false);
        $xml_exporter  = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $xml_validator);
        $xml_exporter->export(new SimpleXMLElement('<empty/>'));
    }
}

class CardwallConfigXmlExport_ColumnsTest extends TuleapTestCase {

    /** @var CardwallConfigXmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    public function setUp() {
        parent::setUp();

        $this->project  = stub('Project')->getId()->returns(140);
        $this->tracker1 = aTracker()->withId(214)->build();
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config = mock('Cardwall_OnTop_Config');
        stub($this->cardwall_config)->isEnabled()->returns(true);

        $this->tracker_factory = stub('TrackerFactory')->getTrackersByGroupId(140)->returns(array(214 => $this->tracker1));

        $this->config_factory = mock('Cardwall_OnTop_ConfigFactory');
        stub($this->config_factory)->getOnTopConfig($this->tracker1)->returns($this->cardwall_config);

        $this->xml_validator = stub('XmlValidator')->nodeIsValid()->returns(true);

        $this->xml_exporter = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $this->xml_validator);
    }

    public function itDumpsNoColumnsWhenNoColumnsDefined() {
        stub($this->cardwall_config)->getDashboardColumns()->returns(new Cardwall_OnTop_Config_ColumnCollection(array()));

        $this->xml_exporter->export($this->root);
        $this->assertEqual(count($this->root->cardwall->trackers->tracker->children()), 0);
    }

    public function itDumpsColumnsAsDefined() {
        stub($this->cardwall_config)->getDashboardColumns()->returns(new Cardwall_OnTop_Config_ColumnCollection(array(
            new Cardwall_Column(112, "Todo", "red", "green", "blue"),
            new Cardwall_Column(113, "On going", "axelle", "red", "est raide")
        )));

        $this->xml_exporter->export($this->root);
        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;
        $this->assertCount($column_xml, 2);
    }
}

?>