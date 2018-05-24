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

use TuleapTestCase;

require_once __DIR__.'/../bootstrap.php';


class TimeCheckerTest extends TuleapTestCase
{
    /*
     * TimeController
     */
    private $time_controller;

    /*
    * TimeChecker
    */
    private $time_checker;

    public function setUp()
    {
        parent::setUp();

        $this->user            = aUser()->withId(102)->build();
        $this->time_retriever  = \Mockery::spy(TimeRetriever::class);
        $this->time_checker    = new TimeChecker($this->time_retriever);

        $this->time            = \Mockery::spy(Time::class);

        $this->artifact        = \Mockery::spy(\Tracker_Artifact::class);
        $this->artifact->shouldReceive([
            'getId'      => 200
        ]);
    }

    public function itReturnFalseIfEqual()
    {
        $this->time->allows()->getUserId()->andReturns(102);
        $this->assertFalse($this->time_checker->doesTimeBelongsToUser($this->time, $this->user));
    }

    public function itReturnTrueIfNotEqual()
    {
        $this->time->allows()->getUserId()->andReturns(103);
        $this->assertTrue($this->time_checker->doesTimeBelongsToUser($this->time, $this->user));
    }

    public function itReturnTrueIfTimeNotNull()
    {
        $this->assertTrue($this->time_checker->checkMandatoryTimeValue('01:21'));
    }

    public function itReturnFalseIfTimeNull()
    {
        $this->assertFalse($this->time_checker->checkMandatoryTimeValue(null));
    }

    public function itReturnTimeIfExistingTime()
    {
        $this->time_retriever->allows()->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-04-04')->andReturns($this->time);
        $this->assertEqual($this->time, $this->time_checker->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-04-04'));
    }

    public function itReturnNullIfExistingTime()
    {
        $this->time_retriever->allows()->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-04-04')->andReturns(null);
        $this->assertNull($this->time_checker->getExistingTimeForUserInArtifactAtGivenDate($this->user, $this->artifact, '2018-04-04'));
    }
}
