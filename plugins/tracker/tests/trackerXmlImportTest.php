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

class TrackerXmlImportTest extends TuleapTestCase {

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function setUp() {

    $this->xml_input =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
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
              <cardwall/>
              <agiledashboard/>
            </project>');

        $this->group_id = 145;

        $this->xml_tracker1 = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>t10</name>
                    <item_name>t11</item_name>
                    <description>t12</description>
                  </tracker>'
        );

        $this->xml_tracker2 = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t20</name>
                    <item_name>t21</item_name>
                    <description>t22</description>
                  </tracker>'
        );

        $this->xml_tracker3 = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $this->xml_trackers_list = array("T101" => $this->xml_tracker1, "T102" => $this->xml_tracker2, "T103" => $this->xml_tracker3);
        $this->mapping = array(
            "T101" => 444,
            "T102" => 555, 
            "T103" => 666
        );

        $this->tracker1 = aTracker()->withId(444)->build();
        $this->tracker2 = aTracker()->withId(555)->build();
        $this->tracker3 = aTracker()->withId(666)->build();

        $this->tracker_factory = mock('TrackerFactory');
        stub($this->tracker_factory)->createFromXml($this->xml_tracker1, $this->group_id, 't10', 't11', 't12')->returns($this->tracker1);
        stub($this->tracker_factory)->createFromXml($this->xml_tracker2, $this->group_id, 't20', 't21', 't22')->returns($this->tracker2);
        stub($this->tracker_factory)->createFromXml($this->xml_tracker3, $this->group_id, 't30', 't31', 't32')->returns($this->tracker3);

        $this->event_manager = mock('EventManager');

        $this->hierarchy_dao = stub('Tracker_Hierarchy_Dao')->updateChildren()->returns(true);

        $this->tracker_xml_importer = new TrackerXmlImport($this->group_id, $this->xml_input, $this->tracker_factory, $this->event_manager, $this->hierarchy_dao);
    
        $GLOBALS['Response'] = new MockResponse();

        $created_tracker1 = mock('Tracker');
        $created_tracker2 = mock('Tracker');
        $created_tracker3 = mock('Tracker');

        stub($created_tracker1)->getId()->returns(444);
        stub($created_tracker2)->getId()->returns(555);
        stub($created_tracker3)->getId()->returns(666);

        $this->tracker_factory->setReturnValueAt(0, 'createFromXML', $created_tracker1);
        $this->tracker_factory->setReturnValueAt(1, 'createFromXML', $created_tracker2);
        $this->tracker_factory->setReturnValueAt(2, 'createFromXML', $created_tracker3);
    }

    public function itReturnsEachSimpleXmlTrackerFromTheXmlInput() {
        $trackers_result = $this->tracker_xml_importer->getAllXmlTrackers();
        $diff = array_diff($trackers_result, $this->xml_trackers_list);

        $this->assertEqual(count($trackers_result), 3);
        $this->assertTrue(empty($diff));
    }

    public function itCreatesAllTrackersAndStoresTrackersHierarchy() {
        $this->tracker_factory->expectCallCount('createFromXML', 3);
        $this->hierarchy_dao->expectCallCount('updateChildren',2);

        $result = $this->tracker_xml_importer->import();

        $this->assertEqual($result, $this->mapping);
    }

    public function itRaisesAnExceptionIfATrackerCannotBeCreatedAndDoesNotContinue() {
        $tracker_factory = mock('TrackerFactory');
        stub($tracker_factory)->createFromXml()->returns(null);
        $this->tracker_xml_importer = new TrackerXmlImport($this->group_id, $this->xml_input, $tracker_factory, $this->event_manager, $this->hierarchy_dao);

        $this->expectException();
        $tracker_factory->expectCallCount('createFromXML', 1);
        $this->tracker_xml_importer->import();
    }

    public function itThrowsAnEventIfAllTrackersAreCreated() {
        expect($this->event_manager)->processEvent(
            Event::IMPORT_XML_PROJECT_TRACKER_DONE,
            array(
                'project_id' => $this->group_id,
                'xml_content' => $this->xml_input,
                'mapping' => $this->mapping
            )
        )->once();

        $this->tracker_factory->expectCallCount('createFromXML', 3);
        $this->tracker_xml_importer->import();
    }

    public function itBuildsTrackersHierarchy() {
        $hierarchy = array();
        $expected_hierarchy = array(444 => array(555));
        $mapper = array("T101" => 444, "T102" => 555);
        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $this->xml_tracker2, $mapper);
        $diff = array_diff($hierarchy, $expected_hierarchy);

        $this->assertTrue(! empty($hierarchy));
        $this->assertNotNull($hierarchy[444]);
        $this->assertTrue(empty($diff));
    }

    public function itAddsTrackersHierarchyOnExistingHierarchy() {
        $hierarchy          = array(444 => array(555));
        $expected_hierarchy = array(444 => array(555, 666));
        $mapper             = array("T101" => 444, "T103" => 666);
        $xml_tracker        = new SimpleXMLElement(
                 '<tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);
        $diff = array_diff($hierarchy, $expected_hierarchy);

        $this->assertTrue(! empty($hierarchy));
        $this->assertNotNull($hierarchy[444]);
        $this->assertTrue(empty($diff));
    }
}
?>
