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

declare(strict_types=1);

namespace Tuleap\Tracker\Hierarchy;

use Tracker_Hierarchy_HierarchicalTracker;
use TreeNode;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HierarchyPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
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

        $presenter = new HierarchyPresenter(
            $tracker,
            $possible_children,
            new TreeNode(),
            [],
            CSRFSynchronizerTokenStub::buildSelf()
        );

        $attributes = $presenter->getPossibleChildren();
        self::assertSame('Stories', $attributes[0]['name']);
        self::assertSame(1, $attributes[0]['id']);
        self::assertSame('', $attributes[0]['selected']);
        self::assertSame('Tasks', $attributes[1]['name']);
        self::assertSame(2, $attributes[1]['id']);
        self::assertSame('selected="selected"', $attributes[1]['selected']);
    }
}
