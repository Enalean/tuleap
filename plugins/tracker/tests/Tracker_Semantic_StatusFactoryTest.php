<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once('bootstrap.php');
Mock::generate('Tracker');

Mock::generate('Tracker_FormElement_Field_List');

class Tracker_Semantic_StatusFactoryTest extends TuleapTestCase
{

    private $xml_security;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testImport()
    {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticStatusTest.xml');

        $tracker = new MockTracker();

        $f1 = new MockTracker_FormElement_Field_List();
        $f1->setReturnValue('getId', 111);
        $f2 = new MockTracker_FormElement_Field_List();
        $f2->setReturnValue('getId', 112);
        $f3 = new MockTracker_FormElement_Field_List();
        $f3->setReturnValue('getId', 113);

        $mapping = array(
                    'F9'  => $f1,
                    'F14' => $f3,
                    'F13' => $f2,
                    'F14-V61' => 801,
                    'F14-V62' => 802,
                    'F14-V63' => 803,
                    'F14-V64' => 804,
                    'F14-V65' => 805,
                    'F14-V66' => 806,
                    'F14-V67' => 807,
                    'F14-V68' => 808,
                    'F14-V69' => 809
                  );
        $semantic_status = Tracker_Semantic_StatusFactory::instance()->getInstanceFromXML($xml, $mapping, $tracker);

        $this->assertEqual($semantic_status->getShortName(), 'status');
        $this->assertEqual($semantic_status->getFieldId(), 113);
        $this->assertEqual(count($semantic_status->getOpenValues()), 4);
        $this->assertTrue(in_array(806, $semantic_status->getOpenValues()));
        $this->assertTrue(in_array(809, $semantic_status->getOpenValues()));
        $this->assertTrue(in_array(807, $semantic_status->getOpenValues()));
        $this->assertTrue(in_array(808, $semantic_status->getOpenValues()));
    }
}
