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

class TimeRetrieverTest extends TuleapTestCase
{
    /**
     * @var TimeRetriever
     */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->dao                   = mock('Tuleap\Timetracking\Time\TimeDao');
        $this->permissions_retriever = mock('Tuleap\Timetracking\Permissions\PermissionsRetriever');

        $this->retriever = new TimeRetriever($this->dao, $this->permissions_retriever);

        $this->user     = aUser()->withId(102)->build();
        $this->tracker  = aMockTracker()->build();
        $this->artifact = aMockArtifact()->withTracker($this->tracker)->withId(200)->build();
    }

    public function itReturnsAnEmptyArrayIfUserIsNotAbleToReadTimes()
    {
        stub($this->permissions_retriever)->userCanSeeAggregatedTimesInTracker($this->user, $this->tracker)->returns(false);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(false);

        expect($this->dao)->getTimesAddedInArtifactByUser(102, 200)->never();

        $this->assertArrayEmpty($this->retriever->getTimesForUser($this->user, $this->artifact));
    }

    public function itRetrievesTimesIfTheUserIsWriter()
    {
        stub($this->permissions_retriever)->userCanSeeAggregatedTimesInTracker($this->user, $this->tracker)->returns(false);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(true);
        stub($this->dao)->getTimesAddedInArtifactByUser()->returns(array());

        expect($this->dao)->getTimesAddedInArtifactByUser(102, 200)->once();
        expect($this->dao)->getAllTimesAddedInArtifact(200)->never();

        $this->retriever->getTimesForUser($this->user, $this->artifact);
    }

    public function itRetrievesTimesIfTheUserIsGlobalReader()
    {
        stub($this->permissions_retriever)->userCanSeeAggregatedTimesInTracker($this->user, $this->tracker)->returns(true);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(false);
        stub($this->dao)->getAllTimesAddedInArtifact()->returns(array());

        expect($this->dao)->getTimesAddedInArtifactByUser(102, 200)->never();
        expect($this->dao)->getAllTimesAddedInArtifact(200)->once();

        $this->retriever->getTimesForUser($this->user, $this->artifact);
    }
}
