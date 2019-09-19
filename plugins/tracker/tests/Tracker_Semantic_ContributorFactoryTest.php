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

class Tracker_Semantic_ContributorFactoryTest extends TuleapTestCase
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
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticContributorTest.xml');

        $tracker = new MockTracker();

        $f1 = new MockTracker_FormElement_Field_List();
        $f1->setReturnValue('getId', 111);
        $f2 = new MockTracker_FormElement_Field_List();
        $f2->setReturnValue('getId', 112);
        $f3 = new MockTracker_FormElement_Field_List();
        $f3->setReturnValue('getId', 113);

        $mapping = array(
                    'F9'  => $f1,
                    'F13'  => $f2,
                    'F16' => $f3
                  );
        $semantic_contributor = Tracker_Semantic_ContributorFactory::instance()->getInstanceFromXML($xml, $mapping, $tracker);

        $this->assertEqual($semantic_contributor->getShortName(), 'contributor');
        $this->assertEqual($semantic_contributor->getFieldId(), 112);
    }
}
