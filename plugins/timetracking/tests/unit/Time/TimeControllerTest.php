<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Time;

use Codendi_Request;
use Tracker;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Timetracking\Exceptions\TimeTrackingNoTimeException;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimeControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimeUpdater
     */
    private $time_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimeRetriever
     */
    private $time_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Codendi_Request
     */
    private $request;
    private TimeController $time_controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;
    private Time $time;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    private CSRFSynchronizerTokenStub $csrf;

    public function setUp(): void
    {
        parent::setUp();

        $this->time_updater    = $this->createMock(\Tuleap\Timetracking\Time\TimeUpdater::class);
        $this->time_retriever  = $this->createMock(TimeRetriever::class);
        $this->request         = $this->createMock(Codendi_Request::class);
        $this->time_controller = new TimeController($this->time_updater, $this->time_retriever);

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(102);

        $this->tracker  = $this->createMock(Tracker::class);
        $this->time     = new Time(83, 102, 2, '2018-04-04', 81, 'g');
        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->tracker->method('getGroupId')->willReturn(102);
        $this->tracker->method('getId')->willReturn(16);

        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->artifact->method('getId')->willReturn(200);

        $this->request->method('get')->with('time-id')->willReturn(83);

        $this->csrf = CSRFSynchronizerTokenStub::buildSelf();
    }

    public function testItThrowsTimeTrackingNoTimeExceptionToDeleteTime(): void
    {
        $this->time_retriever->method('getTimeByIdForUser')->with($this->user, $this->time->getId())->willReturn(null);
        $this->expectException(TimeTrackingNoTimeException::class);

        $this->time_controller->deleteTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItThrowsTimeTrackingNoTimeExceptionToEditTime(): void
    {
        $this->time_retriever->method('getTimeByIdForUser')->with($this->user, $this->time->getId())->willReturn(null);
        $this->expectException(TimeTrackingNoTimeException::class);

        $this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }
}
