<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class CardwallConfigXmlImportTest extends TuleapTestCase {

    public function setUp() {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
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
                    <tracker id="T102"/>
                </trackers>
              </cardwall>
              <agiledashboard/>
            </project>');

        $mapper                           = array("T101" => 444, "T102" => 555, "T103" => 666);
        $this->cardwall_ontop_dao         = mock('Cardwall_OnTop_Dao');
        $this->cardwall_config_xml_import = new CardwallConfigXmlImport($xml, $mapper, $this->cardwall_ontop_dao);
    }

    public function itReturnsAllTrackersIdWithACardwall() {
        $expected    = array(444,555);
        $tracker_ids = $this->cardwall_config_xml_import->getAllTrackersId();

        $this->assertEqual($tracker_ids, $expected);
    }

    public function itStoresAllTheCardwallOnTop() {
        $this->cardwall_ontop_dao->expectCallCount('enable', 2);
        $this->cardwall_config_xml_import->import();
    }
}

?>