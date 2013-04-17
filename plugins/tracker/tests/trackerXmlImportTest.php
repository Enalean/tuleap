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

    }

    public function itReturnsEachSimpleXmlTrackerFromTheXmlInput() {
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

//        $tracker1 = aTracker()->withId(101)->build();
//        $tracker2 = aTracker()->withId(102)->build();
//        $tracker3 = aTracker()->withId(103)->build();

        $xml_tracker1 = new SimpleXMLElement('<tracker ID="T101"/>');
        $xml_tracker2 = new SimpleXMLElement('<tracker ID="T102"/>');
        $xml_tracker3 = new SimpleXMLElement('<tracker ID="T103"/>');

        $expected_trackers = array(101 => $xml_tracker1, 102 => $xml_tracker2, 103 => $xml_tracker3);

        $tracker_xml_importer = new trackerXmlImport($xml_input);
        $trackers = $tracker_xml_importer->getAllXmlTrackers();

        $this->assertEqual(count($trackers), 3);
        $this->assertEqual($trackers, $expected_trackers);
    }

}
?>
