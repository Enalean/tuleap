<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Hierarchy\SearchParentTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParentInHierarchyRetrieverTest extends TestCase
{
    private \Tracker $parent_tracker;
    private SearchParentTrackerStub $search_parent_tracker;
    private RetrieveTrackerStub $retrieve_tracker;

    protected function setUp(): void
    {
        $this->parent_tracker = TrackerTestBuilder::aTracker()->withId(243)->withName('Parent Tracker')->build();

        $this->search_parent_tracker = SearchParentTrackerStub::withNoParent();
        $this->retrieve_tracker      = RetrieveTrackerStub::withoutTracker();
    }

    private function getParent(): \Tuleap\Option\Option
    {
        $child_tracker = TrackerTestBuilder::aTracker()->withName('Child Tracker')->build();

        $retriever = new ParentInHierarchyRetriever(
            $this->search_parent_tracker,
            $this->retrieve_tracker
        );
        return $retriever->getParentTracker($child_tracker);
    }

    public function testItRetrievesTheParentTrackerOfGivenTracker(): void
    {
        $this->search_parent_tracker = SearchParentTrackerStub::withParentTracker($this->parent_tracker->getId());
        $this->retrieve_tracker      = RetrieveTrackerStub::withTracker($this->parent_tracker);

        self::assertSame($this->parent_tracker, $this->getParent()->unwrapOr(null));
    }

    public function testItReturnsNothingWhenGivenTrackerHasNoParentInHierarchy(): void
    {
        $this->search_parent_tracker = SearchParentTrackerStub::withNoParent();

        self::assertTrue($this->getParent()->isNothing());
    }

    public function testItReturnsNothingWhenParentTrackerCantBeFound(): void
    {
        $this->search_parent_tracker = SearchParentTrackerStub::withParentTracker($this->parent_tracker->getId());
        $this->retrieve_tracker      = RetrieveTrackerStub::withoutTracker();

        self::assertTrue($this->getParent()->isNothing());
    }
}
