<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../include/Tracker/Report/Tracker_ReportFactory.class.php');
Mock::generatePartial('Tracker_ReportFactory',
                      'Tracker_ReportFactoryTestVersion',
                      array('getCriteriaFactory', 'getRendererFactory'));
require_once(dirname(__FILE__).'/../include/Tracker/Report/Tracker_Report_CriteriaFactory.class.php');
Mock::generate('Tracker_Report_CriteriaFactory');
require_once(dirname(__FILE__).'/../include/Tracker/Report/Tracker_Report_RendererFactory.class.php');
Mock::generate('Tracker_Report_RendererFactory');

class Tracker_ReportFactoryTest extends TuleapTestCase {

    public function tearDown() {
        foreach ($_SESSION as $key => $nop) {
            unset($_SESSION[$key]);
        }
        parent::tearDown();
    }

    //testing CannedResponse import
    public function testImport() {
        $repo = new Tracker_ReportFactoryTestVersion();
        $crit = new MockTracker_Report_CriteriaFactory();
        $repo->setReturnReference('getCriteriaFactory', $crit);
        $rend = new MockTracker_Report_RendererFactory();
        $repo->setReturnReference('getRendererFactory', $rend);
        
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/TestTracker-1.xml');
        $reports = array();
        foreach ($xml->reports->report as $report) {
            $empty_array = array();
            $reports[] = $repo->getInstanceFromXML($report, $empty_array, 0);
        }
        
        //general settings
        $this->assertEqual($reports[0]->name, 'Default');
        $this->assertEqual($reports[0]->description, 'The system default artifact report');
        $this->assertEqual($reports[0]->is_default, 0);
        
        //default values
        $this->assertEqual($reports[0]->is_query_displayed, 1);
    }
}
?>