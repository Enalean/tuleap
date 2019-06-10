<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

class Tracker_Hierarchy_PresenterTest extends TuleapTestCase
{

    public function testGetPossibleChildrenReturnsAttributesForSelect()
    {
        $possible_children = array(
            1 => aTracker()->withId(1)->withName('Stories')->build(),
            2 => aTracker()->withId(2)->withName('Tasks')->build()
        );

        $tracker = \Mockery::spy(\Tracker_Hierarchy_HierarchicalTracker::class);
        $tracker->shouldReceive('getUnhierarchizedTracker')->andReturns(aTracker()->build());
        $tracker->shouldReceive('hasChild')->with($possible_children[1])->andReturns(false);
        $tracker->shouldReceive('hasChild')->with($possible_children[2])->andReturns(true);

        $presenter = new Tracker_Hierarchy_Presenter(
            $tracker,
            $possible_children,
            new TreeNode(),
            array(),
            []
        );

        $attributes = $presenter->getPossibleChildren();
        $this->assertEqual($attributes[0]['name'], 'Stories');
        $this->assertEqual($attributes[0]['id'], 1);
        $this->assertEqual($attributes[0]['selected'], null);
        $this->assertEqual($attributes[1]['name'], 'Tasks');
        $this->assertEqual($attributes[1]['id'], 2);
        $this->assertEqual($attributes[1]['selected'], 'selected="selected"');
    }
}
