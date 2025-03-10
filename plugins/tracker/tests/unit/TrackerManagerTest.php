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

declare(strict_types=1);

namespace Tuleap\Tracker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Reference;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private \Tracker&MockObject $tracker;
    private \PFUser $user;
    private \Tracker_URL&Stub $url;
    private Artifact&MockObject $artifact;
    private \Tracker_Report&MockObject $report;
    private \Tracker_FormElement_Interface&MockObject $formElement;
    private \TrackerManager&MockObject $tm;

    protected function setUp(): void
    {
        $GLOBALS['HTML'] = $this->createMock(BaseLayout::class);

        $this->user = UserTestBuilder::aUser()->withId(666)->build();

        $this->url = $this->createStub(\Tracker_URL::class);

        $project = ProjectTestBuilder::aProject()->build();

        $this->artifact = $this->createMock(Artifact::class);
        $af             = $this->createMock(\Tracker_ArtifactFactory::class);
        $af->method('getArtifactById')->with('1')->willReturn($this->artifact);

        $this->report = $this->createMock(\Tracker_Report::class);
        $rf           = $this->createMock(\Tracker_ReportFactory::class);
        $rf->method('getReportById')->with('2', $this->user->getId(), true)->willReturn($this->report);

        $this->tracker = $this->createMock(\Tracker::class);
        $this->tracker->method('isActive')->willReturn(true);
        $this->tracker->method('getTracker')->willReturn($this->tracker);

        $trackers = [$this->tracker];

        $tf = $this->createMock(\TrackerFactory::class);
        $tf->method('getTrackerById')->with(3)->willReturn($this->tracker);
        $tf->method('getTrackersByGroupId')->willReturn($trackers);

        $this->formElement = $this->createMock(\Tracker_FormElement_Interface::class);
        $ff                = $this->createMock(\Tracker_FormElementFactory::class);
        $ff->method('getFormElementById')->with('4')->willReturn($this->formElement);

        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->report->method('getTracker')->willReturn($this->tracker);
        $this->formElement->method('getTracker')->willReturn($this->tracker);
        $this->tracker->method('getGroupId')->willReturn(5);
        $this->tracker->method('getProject')->willReturn($project);

        $this->tm = $this->createPartialMock(\TrackerManager::class, [
            'getUrl',
            'getTrackerFactory',
            'getArtifactFactory',
            'getArtifactReportFactory',
            'checkServiceEnabled',
        ]);
        $this->tm->method('getUrl')->willReturn($this->url);
        $this->tm->method('getTrackerFactory')->willReturn($tf);
        $this->tm->method('getArtifactFactory')->willReturn($af);
        $this->tm->method('getArtifactReportFactory')->willReturn($rf);
        $this->tm->method('checkServiceEnabled')->willReturn(true);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['group_id'], $GLOBALS['HTML']);

        parent::tearDown();
    }

    public function testProcessArtifact(): void
    {
        $this->artifact->expects(self::once())->method('process');
        $this->tracker->expects(self::never())->method('process');
        $this->formElement->expects(self::never())->method('process');
        $this->report->expects(self::never())->method('process');

        $request_artifact = HTTPRequestBuilder::get()->withParam('aid', '1')->build();
        $this->artifact->method('userCanView')->willReturn(true);
        $this->tracker->method('userCanView')->willReturn(true);
        $this->url->method('getDispatchableFromRequest')->willReturn($this->artifact);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessReport(): void
    {
        $this->artifact->expects(self::never())->method('process');
        $this->report->expects(self::once())->method('process');
        $this->tracker->expects(self::never())->method('process');
        $this->formElement->expects(self::never())->method('process');

        $request_artifact = HTTPRequestBuilder::get()->build();
        $this->tracker->method('userCanView')->willReturn(true);
        $this->url->method('getDispatchableFromRequest')->willReturn($this->report);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessTracker(): void
    {
        $this->artifact->expects(self::never())->method('process');
        $this->report->expects(self::never())->method('process');
        $this->tracker->expects(self::once())->method('process');
        $this->formElement->expects(self::never())->method('process');
        $this->tracker->expects(self::once())->method('userCanView')->willReturn(true);

        $request_artifact = HTTPRequestBuilder::get()->build();
        $this->url->method('getDispatchableFromRequest')->willReturn($this->tracker);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessTrackerWithNoPermissionsToView(): void
    {
        $this->artifact->expects(self::never())->method('process');
        $this->report->expects(self::never())->method('process');
        $this->tracker->expects(self::never())->method('process'); //user can't view the tracker. so don't process the request in tracker
        $this->formElement->expects(self::never())->method('process');
        $this->tracker->expects(self::once())->method('userCanView')->willReturn(false);
        $GLOBALS['Response']->expects(self::atLeastOnce())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects(self::once())->method('redirect');

        $request_artifact = HTTPRequestBuilder::get()->build();

        $this->url->method('getDispatchableFromRequest')->willReturn($this->tracker);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessField(): void
    {
        $this->artifact->expects(self::never())->method('process');
        $this->report->expects(self::never())->method('process');
        $this->tracker->expects(self::never())->method('process');
        $this->formElement->expects(self::once())->method('process');

        $request_artifact = HTTPRequestBuilder::get()->withParam('formElement', '4')->withParam('group_id', '5')->build();
        $this->tracker->expects(self::once())->method('userCanView')->willReturn(true);
        $this->url->method('getDispatchableFromRequest')->willReturn($this->formElement);
        $this->tm->process($request_artifact, $this->user);
    }

    public function testProcessItself(): void
    {
        $request_artifact = HTTPRequestBuilder::get()->withParam('group_id', '5')->build();

        $tm      = $this->createPartialMock(\TrackerManager::class, [
            'getProject',
            'checkServiceEnabled',
            'displayAllTrackers',
        ]);
        $project = ProjectTestBuilder::aProject()->build();
        $tm->expects(self::once())->method('getProject')->with(5)->willReturn($project);
        $tm->method('checkServiceEnabled')->with($project, $request_artifact)->willReturn(true);
        $tm->expects(self::once())->method('displayAllTrackers')->with($project, $this->user);

        $this->artifact->expects(self::never())->method('process');
        $this->report->expects(self::never())->method('process');
        $this->tracker->expects(self::never())->method('process');
        $this->formElement->expects(self::never())->method('process');

        $tm->process($request_artifact, $this->user);
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
    public function testDuplicateCopyReferences(): void
    {
        $source_project_id      = 100;
        $destination_project_id = 120;
        $u_group_mapping        = [];

        $tm = $this->createPartialMock(\TrackerManager::class, [
            'getTrackerFactory',
            'getReferenceManager',
        ]);

        $tf = $this->createMock(\TrackerFactory::class);
        $tf->expects(self::once())->method('duplicate');
        $tm->method('getTrackerFactory')->willReturn($tf);

        $r1 = new Reference(101, 'bug', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1, 100);
        $r2 = new Reference(102, 'issue', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1, 100);
        $r3 = new Reference(103, 'task', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1, 100);

        $rm = $this->createMock(\ReferenceManager::class);
        $rm->expects(self::once())->method('getReferencesByGroupId')->with($source_project_id)->willReturn([$r1, $r2, $r3]);
        $tm->method('getReferenceManager')->willReturn($rm);

        $t1 = TrackerTestBuilder::aTracker()->withShortName('bug')->build();
        $t2 = $this->createMock(\Tracker::class);
        $t2->method('getItemName')->willReturn('task');
        $t2->method('mustBeInstantiatedForNewProjects')->willReturn(false);

        $tf->method('getTrackersByGroupId')->with($source_project_id)->willReturn([$t1, $t2]);

        $rm->expects(self::once())->method('createReference')->with($r2);

        $tm->duplicate(
            UserTestBuilder::buildWithDefaults(),
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
            ProjectTestBuilder::aProject()->withId($source_project_id)->build(),
            ProjectTestBuilder::aProject()->withId($destination_project_id)->build(),
            new MappingRegistry($u_group_mapping)
        );
    }
}
