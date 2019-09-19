<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('bootstrap.php');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Interface');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_Report');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_ReportFactory');
Mock::generatePartial(
    'Tracker_URL',
    'Tracker_URLTestVersion',
    array(
                          'getTrackerFactory',
                          'getTracker_FormElementFactory',
                          'getArtifactFactory',
                          'getArtifactReportFactory',
                      )
);

Mock::generate('Codendi_Request');
Mock::generate('PFUser');

class Tracker_URLTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->user = mock('PFUser');
        $this->user->setReturnValue('getId', 666);

        $this->artifact = new MockTracker_Artifact($this);
        $af = new MockTracker_ArtifactFactory($this);
        $af->setReturnReference('getArtifactById', $this->artifact, array('1'));

        $this->report = new MockTracker_Report($this);
        $rf = new MockTracker_ReportFactory($this);
        $rf->setReturnReference('getReportById', $this->report, array('2', $this->user->getId(), true));

        $this->tracker = new MockTracker($this);
        $this->tracker->setReturnValue('isActive', true);
        $this->tracker->setReturnValue('userCanView', true);
        $tf = new MockTrackerFactory($this);
        $tf->setReturnReference('getTrackerById', $this->tracker, array(3));

        $this->formElement = new MockTracker_FormElement_Interface($this);
        $ff = new MockTracker_FormElementFactory($this);
        $ff->setReturnReference('getFormElementById', $this->formElement, array('4'));

        $this->artifact->setReturnReference('getTracker', $this->tracker);
        $this->report->setReturnReference('getTracker', $this->tracker);
        $this->formElement->setReturnReference('getTracker', $this->tracker);

        $this->url = new Tracker_URLTestVersion($this);
        $this->url->setReturnReference('getTrackerFactory', $tf);
        $this->url->setReturnReference('getTracker_FormElementFactory', $ff);
        $this->url->setReturnReference('getArtifactFactory', $af);
        $this->url->setReturnReference('getArtifactReportFactory', $rf);
    }
    public function tearDown()
    {
        unset($this->user);
        unset($this->artifact);
        unset($this->tracker);
        unset($this->formElement);
        unset($this->report);
        unset($this->url);
        parent::tearDown();
    }

    public function testGetArtifact()
    {
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '1', array('aid'));
        $request_artifact->setReturnValue('get', '2', array('report'));
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->assertIsA($this->url->getDispatchableFromRequest($request_artifact, $this->user), 'Tracker_Artifact');
    }

    public function testGetReport()
    {
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '2', array('report'));
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->assertIsA($this->url->getDispatchableFromRequest($request_artifact, $this->user), 'Tracker_Report');
    }

    public function testGetTracker()
    {
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->assertIsA($this->url->getDispatchableFromRequest($request_artifact, $this->user), 'Tracker');
    }

    public function testGetTrackerWithAtid()
    {
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', 3, array('atid'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->assertIsA($this->url->getDispatchableFromRequest($request_artifact, $this->user), 'Tracker');
    }

    public function testGetField()
    {
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->assertIsA($this->url->getDispatchableFromRequest($request_artifact, $this->user), 'Tracker_FormElement_Interface');
    }

    public function testGetNotMatchingElement()
    {
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $exeptionThrown = false;
        try {
            $this->url->getDispatchableFromRequest($request_artifact, $this->user);
        } catch (Exception $e) {
            $exeptionThrown = true;
            $this->assertIsA($e, 'Tracker_NoMachingResourceException');
        }
        $this->assertTrue($exeptionThrown, "Exception not thrown");
    }
}
