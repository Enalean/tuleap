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

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Hierarchy_PresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetPossibleChildrenReturnsAttributesForSelect(): void
    {
        $story = TrackerTestBuilder::aTracker()->withId(1)->withName('Stories')->build();
        $task  = TrackerTestBuilder::aTracker()->withId(2)->withName('Tasks')->build();

        $possible_children = [1 => $story, 2 => $task];

        $tracker = new Tracker_Hierarchy_HierarchicalTracker(
            TrackerTestBuilder::aTracker()->withId(3)->build(),
            [2 => $task],
        );

        $presenter = new Tracker_Hierarchy_Presenter(
            $tracker,
            $possible_children,
            new TreeNode(),
            []
        );

        $attributes = $presenter->getPossibleChildren();
        $this->assertEquals('Stories', $attributes[0]['name']);
        $this->assertEquals(1, $attributes[0]['id']);
        $this->assertEquals(null, $attributes[0]['selected']);
        $this->assertEquals('Tasks', $attributes[1]['name']);
        $this->assertEquals(2, $attributes[1]['id']);
        $this->assertEquals('selected="selected"', $attributes[1]['selected']);
    }
}
