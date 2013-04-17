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

        $xml_input = '<?xml version="1.0" encoding="UTF-8"?>
                      <project>
                        <trackers>
                            <tracker ID="T101"/>
                            <tracker ID="T102"/>
                            <tracker ID="T103"/>
                        </trackers>
                        <cardwall/>
                        <agiledashboard/>
                      </project>';

        $group_id = 145;

        $this->xml_tracker1 = new SimpleXMLElement('<tracker ID="T101"/>');
        $this->xml_tracker2 = new SimpleXMLElement('<tracker ID="T102"/>');
        $this->xml_tracker3 = new SimpleXMLElement('<tracker ID="T103"/>');

        $this->trackers_list = array(101 => $this->xml_tracker1, 102 => $this->xml_tracker2, 103 => $this->xml_tracker3);

        $this->tracker1 = aTracker()->withId(101)->build();
        $this->tracker2 = aTracker()->withId(102)->build();
        $this->tracker3 = aTracker()->withId(103)->build();

        $this->tracker_factory = mock('TrackerFactory');
        stub($this->tracker_factory)->createFromXml($this->xml_tracker1, $group_id, '', '', '')->returns($this->tracker1);
        stub($this->tracker_factory)->createFromXml($this->xml_tracker2, $group_id, '', '', '')->returns($this->tracker2);
        stub($this->tracker_factory)->createFromXml($this->xml_tracker3, $group_id, '', '', '')->returns($this->tracker3);

        TrackerFactory::setInstance($this->tracker_factory);

        $this->tracker_xml_importer = new trackerXmlImport($group_id, $xml_input, $this->tracker_factory);
    }

    public function tearDown() {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    public function itReturnsEachSimpleXmlTrackerFromTheXmlInput() {
        $trackers_result = $this->tracker_xml_importer->getAllXmlTrackers();

        $this->assertEqual(count($trackers_result), 3);
        $this->assertEqual($trackers_result,  $this->trackers_list);
    }

    public function itCreatesAllTrackers() {
        $expected_result = array(101 => $this->tracker1, 102 => $this->tracker2, 103 => $this->tracker3);
        $this->tracker_factory->expectCallCount('createFromXML', 3);

        $result = $this->tracker_xml_importer->import();

        $this->assertEqual($result,$expected_result);
    }

}
?>
