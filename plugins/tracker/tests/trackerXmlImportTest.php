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

require_once 'bootstrap.php';

class trackerXmlImportTest extends TuleapTestCase {

    public function setUp() {

    $this->xml_input = '<?xml version="1.0" encoding="UTF-8"?>
            <project>
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
              <cardwall/>
              <agiledashboard/>
            </project>';

        $this->group_id = 145;

        $xml_tracker1 = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>t10</name>
                    <item_name>t11</item_name>
                    <description>t12</description>
                  </tracker>'
        );

        $xml_tracker2 = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t20</name>
                    <item_name>t21</item_name>
                    <description>t22</description>
                  </tracker>'
        );

        $xml_tracker3 = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $this->xml_trackers_list = array("T101" => $xml_tracker1, "T102" => $xml_tracker2, "T103" => $xml_tracker3);

        $this->tracker1 = aTracker()->withId(444)->build();
        $this->tracker2 = aTracker()->withId(555)->build();
        $this->tracker3 = aTracker()->withId(666)->build();

        $this->tracker_factory = mock('TrackerFactory');
        stub($this->tracker_factory)->createFromXml($xml_tracker1, $this->group_id, 't10', 't11', 't12')->returns($this->tracker1);
        stub($this->tracker_factory)->createFromXml($xml_tracker2, $this->group_id, 't20', 't21', 't22')->returns($this->tracker2);
        stub($this->tracker_factory)->createFromXml($xml_tracker3, $this->group_id, 't30', 't31', 't32')->returns($this->tracker3);

        $this->tracker_xml_importer = new trackerXmlImport($this->group_id, $this->xml_input, $this->tracker_factory);
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function itReturnsEachSimpleXmlTrackerFromTheXmlInput() {
        $trackers_result = $this->tracker_xml_importer->getAllXmlTrackers();
        $this->assertEqual(count($trackers_result), 3);
        $diff = array_diff($trackers_result, $this->xml_trackers_list);
        $this->assertTrue(empty($diff));
    }

    public function itCreatesAllTrackers() {
        $expected_result = array("T101" => 444, "T102" => 555, "T103" => 666);
        $this->tracker_factory->expectCallCount('createFromXML', 3);

        $result = $this->tracker_xml_importer->import();

        $this->assertEqual($result,$expected_result);
    }

    public function itRaiseAnExceptionIfaTrackerCannotBeCreatedAndDoesNotContinue() {

        $tracker_factory = mock('TrackerFactory');
        stub($tracker_factory)->createFromXml()->returns(null);

        $this->tracker_xml_importer = new trackerXmlImport($this->group_id, $this->xml_input, $tracker_factory);

        $this->expectException();
        $tracker_factory->expectCallCount('createFromXML', 1);
        $this->tracker_xml_importer->import();
    }

}
?>
