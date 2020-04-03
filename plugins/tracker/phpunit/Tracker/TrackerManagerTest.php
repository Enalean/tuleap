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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Admin\GlobalAdminController;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TrackerManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private $tracker;

    /**
     * @var array
     */
    private $backup_globals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backup_globals = array_merge([], $GLOBALS);
        $GLOBALS['HTML']     = Mockery::spy(\Layout::class);

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(666);

        $this->url = \Mockery::spy(\Tracker_URL::class);

        $project = \Mockery::spy(\Project::class);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        $af = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $af->shouldReceive('getArtifactById')->with('1')->andReturns($this->artifact);

        $this->report = \Mockery::spy(\Tracker_Report::class);
        $rf = \Mockery::spy(\Tracker_ReportFactory::class);
        $rf->shouldReceive('getReportById')->with('2', $this->user->getId(), true)->andReturns($this->report);

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('isActive')->andReturns(true);
        $this->tracker->shouldReceive('getTracker')->andReturns($this->tracker);

        $trackers       = array($this->tracker);

        $tf = \Mockery::spy(\TrackerFactory::class);
        $tf->shouldReceive('getTrackerById')->with(3)->andReturns($this->tracker);
        $tf->shouldReceive('getTrackersByGroupId')->andReturns($trackers);

        $this->formElement = \Mockery::spy(\Tracker_FormElement_Interface::class);
        $ff = \Mockery::spy(\Tracker_FormElementFactory::class);
        $ff->shouldReceive('getFormElementById')->with('4')->andReturns($this->formElement);

        $this->artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->report->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->formElement->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->tracker->shouldReceive('getGroupId')->andReturns(5);
        $this->tracker->shouldReceive('getProject')->andReturns($project);

        $this->tm = \Mockery::mock(\TrackerManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tm->shouldReceive('getUrl')->andReturns($this->url);
        $this->tm->shouldReceive('getTrackerFactory')->andReturns($tf);
        $this->tm->shouldReceive('getTracker_FormElementFactory')->andReturns($ff);
        $this->tm->shouldReceive('getArtifactFactory')->andReturns($af);
        $this->tm->shouldReceive('getArtifactReportFactory')->andReturns($rf);
        $this->tm->shouldReceive('checkServiceEnabled')->andReturns(true);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['group_id']);
        $GLOBALS = $this->backup_globals;

        parent::tearDown();
    }

    public function testProcessArtifact()
    {
        $this->artifact->shouldReceive('process')->once();
        $this->tracker->shouldReceive('process')->never();
        $this->formElement->shouldReceive('process')->never();
        $this->report->shouldReceive('process')->never();

        $request_artifact = Mockery::spy(HTTPRequest::class);
        $request_artifact->shouldReceive('get')->with('aid')->andReturn('1');
        $this->artifact->shouldReceive('userCanView')->andReturns(true);
        $this->tracker->shouldReceive('userCanView')->andReturns(true);
        $this->url->shouldReceive('getDispatchableFromRequest')->andReturns($this->artifact);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessReport()
    {
        $this->artifact->shouldReceive('process')->never();
        $this->report->shouldReceive('process')->once();
        $this->tracker->shouldReceive('process')->never();
        $this->formElement->shouldReceive('process')->never();

        $request_artifact = Mockery::spy(HTTPRequest::class);
        $this->tracker->shouldReceive('userCanView')->andReturns(true);
        $this->url->shouldReceive('getDispatchableFromRequest')->andReturns($this->report);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessTracker()
    {
        $this->artifact->shouldReceive('process')->never();
        $this->report->shouldReceive('process')->never();
        $this->tracker->shouldReceive('process')->once();
        $this->formElement->shouldReceive('process')->never();
        $this->tracker->shouldReceive('userCanView')->once()->andReturns(true);

        $request_artifact = Mockery::spy(HTTPRequest::class);

        $this->url->shouldReceive('getDispatchableFromRequest')->andReturns($this->tracker);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessTrackerWithNoOermissionsToView()
    {
        $this->artifact->shouldReceive('process')->never();
        $this->report->shouldReceive('process')->never();
        $this->tracker->shouldReceive('process')->never(); //user can't view the tracker. so don't process the request in tracker
        $this->formElement->shouldReceive('process')->never();
        $this->tracker->shouldReceive('userCanView')->once()->andReturns(false);
        $GLOBALS['Response']->expect('addFeedback', array('error', '*', '*'));
        $GLOBALS['Language']->expect('getText', array('plugin_tracker_common_type', 'no_view_permission'));
        $this->tm->shouldReceive('displayAllTrackers')->once();

        $request_artifact = Mockery::spy(HTTPRequest::class);

        $this->url->shouldReceive('getDispatchableFromRequest')->andReturns($this->tracker);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessField()
    {
        $this->artifact->shouldReceive('process')->never();
        $this->report->shouldReceive('process')->never();
        $this->tracker->shouldReceive('process')->never();
        $this->formElement->shouldReceive('process')->once();

        $request_artifact = Mockery::spy(HTTPRequest::class);
        $request_artifact->shouldReceive('get')->with('formElement')->andReturns('4');
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');
        $this->tracker->shouldReceive('userCanView')->andReturns(true);
        $this->url->shouldReceive('getDispatchableFromRequest')->andReturns($this->formElement);
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

        $this->artifact->shouldReceive('process')->never();
        $this->report->shouldReceive('process')->never();
        $this->tracker->shouldReceive('process')->never();
        $this->formElement->shouldReceive('process')->never();

        $request_artifact->shouldReceive('get')->with('group_id')->andReturn('5');
        $tm->process($request_artifact, $this->user);
    }

    public function testSearch()
    {
        $request = Mockery::spy(HTTPRequest::class);
        $request->shouldReceive('exist')->with('tracker')->andReturn(true);
        $request->shouldReceive('get')->with('tracker')->andReturn(3);
        $this->tracker->shouldReceive('userCanView')->with($this->user)->andReturns(true);

        $this->tracker->shouldReceive('displaySearch')->once();
        $this->tm->search($request, $this->user);
    }

    public function testSearchUserCannotViewTracker()
    {
        $request = Mockery::spy(HTTPRequest::class);
        $request->shouldReceive('exist')->with('tracker')->andReturn(true);
        $request->shouldReceive('get')->with('tracker')->andReturn(3);

        $this->tracker->shouldReceive('displaySearch')->never();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', Mockery::any())->once();
        $GLOBALS['HTML']->shouldReceive('redirect')->once();
        $this->tracker->shouldReceive('userCanView')->with($this->user)->andReturns(false);
        $this->tm->search($request, $this->user);
    }

    public function testSearchAllTrackerDisplaySearchNotCalled()
    {
        $request = Mockery::spy(HTTPRequest::class);
        $request->shouldReceive('exist')->with('tracker')->andReturn(false);
        $this->tracker->shouldReceive('userCanView')->with($this->user)->andReturns(true);

        $this->tracker->shouldReceive('displaySearch')->never();
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
