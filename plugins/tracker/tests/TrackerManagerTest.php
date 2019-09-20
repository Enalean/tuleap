<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Admin\GlobalAdminController;

require_once('bootstrap.php');
Mock::generate('Tracker_URL');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Interface');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_Report');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_ReportFactory');
Mock::generatePartial(
    'TrackerManager',
    'TrackerManagerTestVersion',
    array(
                          'getUrl',
                          'getTrackerFactory',
                          'getTracker_FormElementFactory',
                          'getArtifactFactory',
                          'getArtifactReportFactory',
                          'getProject',
                          'displayAllTrackers',
                          'checkServiceEnabled',
                      )
);
Mock::generate('PFUser');
Mock::generate('Layout');
Mock::generate('Project');
Mock::generate('ReferenceManager');


class TrackerManagerTest extends TuleapTestCase
{

    private $tracker;

    public function setUp()
    {
        parent::setUp();
        $this->user = mock('PFUser');
        $this->user->setReturnValue('getId', 666);

        $this->url = new MockTracker_URL();

        $project = new MockProject();

        $this->artifact = new MockTracker_Artifact($this);
        $af = new MockTracker_ArtifactFactory($this);
        $af->setReturnReference('getArtifactById', $this->artifact, array('1'));

        $this->report = new MockTracker_Report($this);
        $rf = new MockTracker_ReportFactory($this);
        $rf->setReturnReference('getReportById', $this->report, array('2', $this->user->getId(), true));

        $this->tracker = new MockTracker($this);
        $this->tracker->setReturnValue('isActive', true);
        $this->tracker->setReturnValue('getTracker', $this->tracker);

        $trackers       = array($this->tracker);

        $tf = new MockTrackerFactory($this);
        $tf->setReturnReference('getTrackerById', $this->tracker, array(3));
        stub($tf)->getTrackersByGroupId()->returns($trackers);

        $this->formElement = new MockTracker_FormElement_Interface($this);
        $ff = new MockTracker_FormElementFactory($this);
        $ff->setReturnReference('getFormElementById', $this->formElement, array('4'));

        $this->artifact->setReturnReference('getTracker', $this->tracker);
        $this->report->setReturnReference('getTracker', $this->tracker);
        $this->formElement->setReturnReference('getTracker', $this->tracker);
        $this->tracker->setReturnValue('getGroupId', 5);
        $this->tracker->setReturnReference('getProject', $project);

        $this->tm = new TrackerManagerTestVersion($this);
        $this->tm->setReturnReference('getUrl', $this->url);
        $this->tm->setReturnReference('getTrackerFactory', $tf);
        $this->tm->setReturnReference('getTracker_FormElementFactory', $ff);
        $this->tm->setReturnReference('getArtifactFactory', $af);
        $this->tm->setReturnReference('getArtifactReportFactory', $rf);
        $this->tm->setReturnValue('checkServiceEnabled', true);
    }
    public function tearDown()
    {
        unset($this->url);
        unset($this->user);
        unset($this->artifact);
        unset($this->tracker);
        unset($this->formElement);
        unset($this->report);
        unset($this->tm);
        parent::tearDown();
    }

    public function testProcessArtifact()
    {
        $this->artifact->expectOnce('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        $this->report->expectNever('process');

        $request_artifact = Mockery::spy(HTTPRequest::class);
        $request_artifact->shouldReceive('get')->with('aid')->andReturn('1');
        $this->artifact->setReturnValue('userCanView', true);
        $this->tracker->setReturnValue('userCanView', true);
        $this->url->setReturnValue('getDispatchableFromRequest', $this->artifact);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessReport()
    {
        $this->artifact->expectNever('process');
        $this->report->expectOnce('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');

        $request_artifact = Mockery::spy(HTTPRequest::class);
        $this->tracker->setReturnValue('userCanView', true);
        $this->url->setReturnValue('getDispatchableFromRequest', $this->report);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessTracker()
    {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectOnce('process');
        $this->formElement->expectNever('process');

        $this->tracker->setReturnValue('userCanView', true);
        $this->tracker->expectOnce('userCanView');

        $request_artifact = Mockery::spy(HTTPRequest::class);

        $this->url->setReturnValue('getDispatchableFromRequest', $this->tracker);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessTracker_with_no_permissions_to_view()
    {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process'); //user can't view the tracker. so don't process the request in tracker
        $this->formElement->expectNever('process');

        $this->tracker->setReturnValue('userCanView', false);
        $this->tracker->expectOnce('userCanView');
        $GLOBALS['Response']->expect('addFeedback', array('error', '*', '*'));
        $GLOBALS['Language']->expect('getText', array('plugin_tracker_common_type', 'no_view_permission'));
        $this->tm->expectOnce('displayAllTrackers');

        $request_artifact = Mockery::spy(HTTPRequest::class);

        $this->url->setReturnValue('getDispatchableFromRequest', $this->tracker);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessField()
    {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectOnce('process');

        $request_artifact = Mockery::spy(HTTPRequest::class);
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->tracker->setReturnValue('userCanView', true);
        $this->url->setReturnValue('getDispatchableFromRequest', $this->formElement);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessItself()
    {
        $request_artifact = Mockery::spy(HTTPRequest::class);

        $tm = Mockery::mock(TrackerManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = Mockery::spy(Project::class);
        $tm->shouldReceive('getProject')->with(5)->andReturn($project)->once();
        $tm->shouldReceive('checkServiceEnabled')->with($project, $request_artifact)->andReturn(true);
        $tm->shouldReceive('getGlobalAdminController')->andReturn(Mockery::mock(GlobalAdminController::class));
        $tm->shouldReceive('displayAllTrackers')->with($project, $this->user)->once();

        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');

        $request_artifact->shouldReceive('get')->with('group_id')->andReturn('5');
        $tm->process($request_artifact, $this->user);
    }

    public function testSearch()
    {
        $request = Mockery::spy(HTTPRequest::class);
        $request->shouldReceive('exist')->with('tracker')->andReturn(true);
        $request->shouldReceive('get')->with('tracker')->andReturn(3);
        $this->tracker->setReturnValue('userCanView', true, array($this->user));

        $this->tracker->expectOnce('displaySearch');
        $this->tm->search($request, $this->user);
    }

    public function testSearchUserCannotViewTracker()
    {
        $request = Mockery::spy(HTTPRequest::class);
        $request->shouldReceive('exist')->with('tracker')->andReturn(true);
        $request->shouldReceive('get')->with('tracker')->andReturn(3);

        $this->tracker->expectNever('displaySearch');
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect');
        $this->tracker->setReturnValue('userCanView', false, array($this->user));
        $this->tm->search($request, $this->user);
    }

    public function testSearchAllTrackerDisplaySearchNotCalled()
    {
        $request = Mockery::spy(HTTPRequest::class);
        $request->shouldReceive('exist')->with('tracker')->andReturn(false);
        $this->tracker->setReturnValue('userCanView', true, array($this->user));

        $this->tracker->expectNever('displaySearch');
        $this->tm->search($request, $this->user);
    }

    /**
     * Given I have 3 plugin_tracker_artifact references in the template project
     * - bug
     * - issue
     * - task
     * And 'bug' correspond to 'Bug' tracker
     * And 'task' correspond to 'Task' tracker
     * And 'issue' was created by hand by the admin
     * And 'Bug' tracker is instanciated for new project
     * And 'Task' tracker is not instanciated for new project
     *
     * Then 'issue' reference is created
     * And 'bug' reference is not created (it's up to tracker creation to create the reference)
     * And 'task' reference is not created (it's up to tracker creation to create the reference)
     *
     */
    public function testDuplicateCopyReferences()
    {
        $source_project_id       = 100;
        $destinatnion_project_id = 120;
        $u_group_mapping         = array();

        $tm = Mockery::mock(TrackerManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $tf = Mockery::mock(TrackerFactory::class);
        $tf->shouldReceive('duplicate')->once();
        $tm->shouldReceive('getTrackerFactory')->andReturns($tf);

        $r1 = new Reference(101, 'bug', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1, 100);
        $r2 = new Reference(102, 'issue', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1, 100);
        $r3 = new Reference(103, 'task', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1, 100);

        $rm = Mockery::mock(ReferenceManager::class);
        $rm->shouldReceive('getReferencesByGroupId')->with($source_project_id)->andReturns([$r1, $r2, $r3])->once();
        $tm->shouldReceive('getReferenceManager')->andReturns($rm);

        $t1 = Mockery::mock(Tracker::class);
        $t1->shouldReceive('getItemName')->andReturns('bug');
        $t1->shouldReceive('mustBeInstantiatedForNewProjects')->andReturns(true);
        $t2 = Mockery::mock(Tracker::class);
        $t2->shouldReceive('getItemName')->andReturns('task');
        $t2->shouldReceive('mustBeInstantiatedForNewProjects')->andReturns(false);

        $tf->shouldReceive('getTrackersByGroupId')->with($source_project_id)->andReturns([$t1, $t2]);

        $rm->shouldReceive('createReference')->with($r2)->once();

        $tm->duplicate($source_project_id, $destinatnion_project_id, $u_group_mapping);
    }
}
