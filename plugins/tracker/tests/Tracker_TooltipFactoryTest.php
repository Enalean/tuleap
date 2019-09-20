<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
require_once('bootstrap.php');
Mock::generate('Tracker');

class Tracker_TooltipFactoryTest extends TuleapTestCase
{

    /** @var XML_Security */
    protected $xml_security;

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

    //testing Tooltip import
    public function testImport()
    {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerSemanticTooltipTest.xml');

        $tracker = new MockTracker();

        $mapping = array(
                    'F8'  => 108,
                    'F9'  => 109,
                    'F16' => 116,
                    'F14' => 114
                    );
        $tooltip = Tracker_TooltipFactory::instance()->getInstanceFromXML($xml, $mapping, $tracker);

        $this->assertEqual(count($tooltip->getFields()), 3);
        $fields = $tooltip->getFields();
        $this->assertTrue(in_array(108, $fields));
        $this->assertTrue(in_array(109, $fields));
        $this->assertTrue(in_array(116, $fields));
        $this->assertFalse(in_array(114, $fields));
    }
}
