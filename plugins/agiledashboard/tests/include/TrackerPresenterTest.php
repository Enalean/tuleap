<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aTracker.php';

class Planning_TrackerPresenter_TestCase extends TuleapTestCase
{
    public function setUp()
    {
        $this->planning         = mock('Planning');
        $this->tracker          = aTracker()->build();
        $this->tracker_id       = $this->tracker->getId();
        $this->other_tracker_id = $this->tracker->getId() + 1;
        $this->presenter        = new Planning_TrackerPresenter($this->planning, $this->tracker);
    }

    public function itHasAnId()
    {
        $this->assertEqual($this->presenter->getId(), $this->tracker_id);
    }

    public function itHasAName()
    {
        $this->assertEqual($this->presenter->getName(), $this->tracker->getName());
    }

    protected function assertSelected($selected)
    {
        $this->assertTrue($selected);
    }

    protected function assertNotSelected($selected)
    {
        $this->assertFalse($selected);
    }
}

class Planning_TrackerPresenter_BacklogTrackerTest extends Planning_TrackerPresenter_TestCase
{
    public function setUp()
    {
        parent::setUp();
        stub($this->planning)->getBacklogTrackersIds()->returns(array($this->tracker_id));
        stub($this->planning)->getPlanningTrackerId()->returns($this->other_tracker_id);
    }

    public function itIsSelectedAsABacklogTracker()
    {
        $this->assertSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function itIsNotSelectedAsAPlanningTracker()
    {
        $this->assertNotSelected($this->presenter->selectedIfPlanningTracker());
    }
}

class Planning_TrackerPresenter_PlanningTrackerTest extends Planning_TrackerPresenter_TestCase
{
    public function setUp()
    {
        parent::setUp();
        stub($this->planning)->getBacklogTrackersIds()->returns(array($this->other_tracker_id));
        stub($this->planning)->getPlanningTrackerId()->returns($this->tracker_id);
    }

    public function itIsNotSelectedAsABacklogTracker()
    {
        $this->assertNotSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function itIsSelectedAsABacklogTracker()
    {
        $this->assertSelected($this->presenter->selectedIfPlanningTracker());
    }
}

class Planning_TrackerPresenter_NonBacklogNorPlanningTrackerTest extends Planning_TrackerPresenter_TestCase
{
    public function setUp()
    {
        parent::setUp();
        stub($this->planning)->getBacklogTrackersIds()->returns(array($this->other_tracker_id));
        stub($this->planning)->getPlanningTrackerId()->returns($this->other_tracker_id);
    }
    public function itIsNotSelectedAsABacklogTracker()
    {
        $this->assertNotSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function itIsNotSelectedAsAPlanningTracker()
    {
        $this->assertNotSelected($this->presenter->selectedIfPlanningTracker());
    }
}
