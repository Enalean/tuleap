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

require_once dirname(__FILE__).'/../include/Cardwall_Config_XmlExport.class.php';
require_once dirname(__FILE__) .'/bootstrap.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class Cardwall_Config_XmlExportTest extends TuleapTestCase {

    /** @var Cardwall_Config_XmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    public function setUp() {
        parent::setUp();

        $project        = stub('Project')->getId()->returns(140);
        $this->tracker1 = aTracker()->withId(214)->build();
        $this->tracker2 = aTracker()->withId(614)->build();
        $this->root     = new SimpleXMLElement('<cardwall/>');

        $cardwall_config  = stub('Cardwall_OnTop_Config')->getMappingFor($this->tracker1)->returns(null);
        $cardwall_config2 = stub('Cardwall_OnTop_Config')->getMappingFor($this->tracker2)->returns(true);
        $cardwall_config3  = stub('Cardwall_OnTop_Config')->getMappingFor($this->tracker2)->returns(null);

        $tracker_factory = stub('TrackerFactory')->getTrackersByGroupId(140)->returns(array(214 => $this->tracker1, 614 => $this->tracker2));
        TrackerFactory::setInstance($tracker_factory);

        $this->config_factory = mock('Cardwall_OnTop_ConfigFactory');
        stub($this->config_factory)->getOnTopConfig($this->tracker1)->returns($cardwall_config);
        stub($this->config_factory)->getOnTopConfig($this->tracker2)->returns($cardwall_config2);

        $this->config_factory2 = mock('Cardwall_OnTop_ConfigFactory');
        stub($this->config_factory2)->getOnTopConfig($this->tracker1)->returns($cardwall_config);
        stub($this->config_factory2)->getOnTopConfig($this->tracker2)->returns($cardwall_config3);

        $this->xml_exporter = new Cardwall_Config_XmlExport($project, $tracker_factory, $this->config_factory);
        $this->xml_exporter2 = new Cardwall_Config_XmlExport($project, $tracker_factory, $this->config_factory2);
    }

    public function tearDown() {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    public function itReturnsTheGoodRootXmlWithTrackers() {
        $this->xml_exporter->exportToXml($this->root);
        $children = $this->root->children();

        $this->assertTrue(count($children) > 0);
        $this->assertEqual(count($children->children()), 1);
    }

     public function itReturnsTheGoodRootXmlWithoutTrackers() {
        $this->xml_exporter2->exportToXml($this->root);
        $children = $this->root->children();

        $this->assertTrue(count($children) > 0);
        $this->assertEqual(count($children->children()), 0);
    }

    public function itCallsGetMappingForMethodForEachTracker() {
        $this->config_factory->expectCallCount('getOnTopConfig', 2);
        $this->xml_exporter->exportToXml($this->root);
    }

}

?>
