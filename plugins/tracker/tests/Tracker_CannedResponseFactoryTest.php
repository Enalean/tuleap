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

Mock::generatePartial(
    'Tracker_CannedResponseFactory',
    'Tracker_CannedResponseFactoryTestVersion',
    array(
        'getTrackerFactory',
        'getCannedResponses',
        'create'
    )
);

Mock::generate('Tracker_CannedResponse');

Mock::generate('Tracker');

Mock::generate('TrackerFactory');

class Tracker_CannedResponseFactoryTest extends TuleapTestCase
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

    //testing CannedResponse import
    public function testImport()
    {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/TestTracker-1.xml');
        $responses = array();
        foreach ($xml->cannedResponses->cannedResponse as $index => $response) {
            $responses[] = Tracker_CannedResponseFactory::instance()->getInstanceFromXML($response);
        }

        $this->assertEqual($responses[0]->title, 'new response');
        $this->assertEqual($responses[0]->body, 'this is the message of the new canned response');
    }

    public function testDuplicateWithNoCannedResponses()
    {
        $from_tracker = new MockTracker();
        $tf = new MockTrackerFactory();
        $tf->setReturnReference('getTrackerById', $from_tracker, array(102));
        $canned_responses = array();

        $crf = new Tracker_CannedResponseFactoryTestVersion();
        $crf->setReturnReference('getTrackerFactory', $tf);
        $crf->setReturnValue('getCannedResponses', $canned_responses, array($from_tracker));
        $crf->expectNever('create', 'Method create should not be called when there is no canned responses in tracker source');
        $crf->duplicate(102, 502);
    }

    public function testDuplicateWithCannedResponses()
    {
        $from_tracker = new MockTracker();
        $to_tracker = new MockTracker();
        $tf = new MockTrackerFactory();
        $tf->setReturnReference('getTrackerById', $from_tracker, array(102));
        $tf->setReturnReference('getTrackerById', $to_tracker, array(502));

        $cr1 = new MockTracker_CannedResponse();
        $cr1->setReturnValue('getTitle', 'cr1');
        $cr1->setReturnValue('getBody', 'body of cr1');
        $cr2 = new MockTracker_CannedResponse();
        $cr2->setReturnValue('getTitle', 'cr2');
        $cr2->setReturnValue('getBody', 'body of cr2');
        $cr3 = new MockTracker_CannedResponse();
        $cr3->setReturnValue('getTitle', 'cr3');
        $cr3->setReturnValue('getBody', 'body of cr3');
        $crs = array($cr1, $cr2, $cr3);

        $crf = new Tracker_CannedResponseFactoryTestVersion();
        $crf->setReturnReference('getTrackerFactory', $tf);
        $crf->setReturnValue('getCannedResponses', $crs, array($from_tracker));
        $crf->expectCallCount('create', 3, 'Method create should be called 3 times.');
        $crf->expectAt(0, 'create', array($to_tracker, 'cr1', 'body of cr1'));
        $crf->expectAt(1, 'create', array($to_tracker, 'cr2', 'body of cr2'));
        $crf->expectAt(2, 'create', array($to_tracker, 'cr3', 'body of cr3'));
        $crf->duplicate(102, 502);
    }
}
