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

use Tracker;
use TuleapTestCase;

require_once __DIR__.'/../bootstrap.php';

class TimeRetrieverTest extends TuleapTestCase
{
    /**
     * @var TimeRetriever
     */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->dao                   = \Mockery::spy(\Tuleap\Timetracking\Time\TimeDao::class);
        $this->permissions_retriever = \Mockery::spy(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);

        $this->retriever = new TimeRetriever($this->dao, $this->permissions_retriever);

        $this->user     = aUser()->withId(102)->build();
        $this->tracker  = \Mockery::spy(Tracker::class);
        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);

        $this->artifact->shouldReceive([
            'getTracker' => $this->tracker,
            'getId'      => 200,
        ]);
    }

    public function tearDown()
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function itReturnsAnEmptyArrayIfUserIsNotAbleToReadTimes()
    {
        $this->permissions_retriever->allows()->userCanSeeAggregatedTimesInTracker($this->user, $this->tracker)->andReturns(false);
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);

        $this->dao->shouldNotReceive('getTimesAddedInArtifactByUser');
        $this->dao->shouldNotReceive('getAllTimesAddedInArtifact');

        $this->assertArrayEmpty($this->retriever->getTimesForUser($this->user, $this->artifact));
    }

    public function itRetrievesTimesIfTheUserIsWriter()
    {
        $this->permissions_retriever->allows()->userCanSeeAggregatedTimesInTracker($this->user, $this->tracker)->andReturns(false);
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);

        $epected_once = $this->dao->allows()->getTimesAddedInArtifactByUser(102, 200)->andReturns([]);
        $epected_once->times(1);

        $this->dao->shouldNotReceive('getAllTimesAddedInArtifact');

        $this->retriever->getTimesForUser($this->user, $this->artifact);
    }

    public function itRetrievesTimesIfTheUserIsGlobalReader()
    {
        $this->permissions_retriever->allows()->userCanSeeAggregatedTimesInTracker($this->user, $this->tracker)->andReturns(true);
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);

        $epected_once = $this->dao->allows()->getAllTimesAddedInArtifact(200)->andReturns([]);
        $epected_once->times(1);

        $this->dao->shouldNotReceive('getTimesAddedInArtifactByUser');

        $this->retriever->getTimesForUser($this->user, $this->artifact);
    }
}
