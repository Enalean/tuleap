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

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HierarchyDAOTest extends TestIntegrationTestCase
{
    private const CHILD_TRACKER_ID  = 116;
    private const PARENT_TRACKER_ID = 253;
    private HierarchyDAO $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = new HierarchyDAO();
    }

    public function testDeleteChildTracker(): void
    {
        $this->dao->changeTrackerHierarchy(self::PARENT_TRACKER_ID, [self::CHILD_TRACKER_ID]);

        $parents = $this->dao->searchAncestorIds(self::CHILD_TRACKER_ID);
        self::assertSame([self::PARENT_TRACKER_ID], $parents);
        $single_parent = $this->dao->searchParentId(self::CHILD_TRACKER_ID);
        self::assertSame(self::PARENT_TRACKER_ID, $single_parent->unwrapOr(null));

        $grand_parents = $this->dao->searchAncestorIds(self::PARENT_TRACKER_ID);
        self::assertEmpty($grand_parents);

        $this->dao->deleteParentChildAssociationsForTracker(self::CHILD_TRACKER_ID);

        $parents_after_delete = $this->dao->searchAncestorIds(self::CHILD_TRACKER_ID);
        self::assertEmpty($parents_after_delete);
        $single_parent_after_delete = $this->dao->searchParentId(self::CHILD_TRACKER_ID);
        self::assertTrue($single_parent_after_delete->isNothing());
    }

    public function testItForbidsHavingMoreThanOneParent(): void
    {
        $other_parent_id = 464;
        $this->dao->changeTrackerHierarchy(self::PARENT_TRACKER_ID, [self::CHILD_TRACKER_ID]);
        $this->dao->changeTrackerHierarchy($other_parent_id, [self::CHILD_TRACKER_ID]);

        $parents = $this->dao->searchAncestorIds(self::CHILD_TRACKER_ID);
        self::assertSame([$other_parent_id], $parents);
    }
}
