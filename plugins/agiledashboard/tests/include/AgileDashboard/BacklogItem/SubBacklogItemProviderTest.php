<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../../bootstrap.php';

class AgileDashboard_BacklogItem_SubBacklogItemProviderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->milestone       = aMilestone()->withArtifact(anArtifact()->withId(3)->build())->build();
        $this->backlog_tracker = aTracker()->build();
        $this->dao             = mock('AgileDashboard_BacklogItem_SubBacklogItemDao');

        $this->epic_tracker  = aTracker()->withId(25)->build();
        $this->story_tracker = aTracker()->withId(29)->build();
        $this->task_tracker  = aTracker()->withId(28)->build();
        $this->parent_backlog_tracker_collection_provider = mock('AgileDashboard_Planning_ParentBacklogTrackerCollectionProvider');
        stub($this->parent_backlog_tracker_collection_provider)
            ->getParentBacklogTrackerCollection($this->backlog_tracker, $this->milestone)
            ->returns(array($this->epic_tracker, $this->story_tracker, $this->task_tracker));

        $this->provider = new AgileDashboard_BacklogItem_SubBacklogItemProvider(
            $this->dao,
            $this->parent_backlog_tracker_collection_provider
        );
    }
    public function itReturnsTheMatchingIds() {
        stub($this->dao)->getAllBacklogItemIdInMilestone(3, array(25,29,28))->returnsDar(
            array('list_of_ids' => '7,8,11')
        );

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker);
        $this->assertEqual(array_keys($result), array(7, 8, 11));
    }

    public function itReturnsAnEmptyResultIfThereIsNoMatchingId() {
        stub($this->dao)->getAllBacklogItemIdInMilestone(3, array(25,29,28))->returnsDar(
            array('list_of_ids' => NULL)
        );

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker);
        $this->assertEqual($result, array());
    }
}