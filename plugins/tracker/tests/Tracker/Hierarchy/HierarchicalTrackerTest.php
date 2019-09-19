<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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
require_once __DIR__.'/../../bootstrap.php';

class Tracker_Hierarchy_HierarchicalTrackerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->project_id = 110;
        $project = new MockProject();
        $project->setReturnValue('getId', $this->project_id);
        $this->tracker  = aTracker()->withId(1)->withProject($project)->build();
        $this->child    = aTracker()->withId(2)->build();
        $this->children = array($this->child);

        $this->hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($this->tracker, $this->children);
    }

    function testDelegatesGetGroupIdToTracker()
    {
        $this->assertEqual($this->hierarchical_tracker->getProject()->getId(), 110);
    }

    function testDelegatesGetIdToTracker()
    {
        $this->assertEqual($this->hierarchical_tracker->getId(), 1);
    }

    function testHasChild()
    {
        $this->assertTrue($this->hierarchical_tracker->hasChild($this->child));
    }

    function testNotHasChild()
    {
        $not_child = aTracker()->withId(3)->build();
        $this->assertFalse($this->hierarchical_tracker->hasChild($not_child));
    }

    function testIsNotItsOwnChild()
    {
        $this->assertFalse($this->hierarchical_tracker->hasChild($this->tracker));
    }

    function testGetChildren()
    {
        $children = $this->hierarchical_tracker->getChildren();
        $this->assertEqual(count($children), 1);
        $this->assertEqual($children[0], $this->child);
    }
}
