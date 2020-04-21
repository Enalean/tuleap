<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use CSRFSynchronizerToken;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Timetracking\Exceptions\TimeTrackingNoTimeException;

require_once __DIR__ . '/../bootstrap.php';

class TimeControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /*
     * TimeController
     */
    private $time_controller;

    /*
     * Time
     */
    private $time;

    public function setUp(): void
    {
        parent::setUp();

        $this->time_updater          = \Mockery::spy(\Tuleap\Timetracking\Time\TimeUpdater::class);
        $this->time_retriever        = \Mockery::spy(TimeRetriever::class);
        $this->request               = \Mockery::spy(Codendi_Request::class);
        $this->time_controller       = new TimeController($this->time_updater, $this->time_retriever);

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->allows()->getId()->andReturns(102);

        $this->tracker  = \Mockery::spy(Tracker::class);
        $this->time     = new Time(83, 102, 2, '2018-04-04', 81, 'g');
        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);

        $this->tracker->shouldReceive([
            'getGroupId' => 102,
            'getId'      => 16
        ]);

        $this->artifact->shouldReceive([
            'getTracker' => $this->tracker,
            'getId'      => 200
        ]);

        $this->request->allows()->get('time-id')->andReturns(83);

        $this->csrf = \Mockery::spy(CSRFSynchronizerToken::class);
        $this->csrf->allows()->check()->andReturns(true);
    }

    public function testItThrowsTimeTrackingNoTimeExceptionToDeleteTime()
    {
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns(null);
        $this->expectException(TimeTrackingNoTimeException::class);

        $this->time_controller->deleteTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }

    public function testItThrowsTimeTrackingNoTimeExceptionToEditTime()
    {
        $this->time_retriever->allows()->getTimeByIdForUser($this->user, $this->time->getId())->andReturns(null);
        $this->expectException(TimeTrackingNoTimeException::class);

        $this->time_controller->editTimeForUser($this->request, $this->user, $this->artifact, $this->csrf);
    }
}
