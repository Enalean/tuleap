<?php
/**
 * Copyright Enalean (c) 2013-present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog\Backlog;

use AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItem;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BacklogItemPresenterCollectionTest extends TestCase
{
    public function testItReturnsFalseWhenCollectionIsEmpty(): void
    {
        $collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        self::assertFalse($collection->containsId(5));
    }

    public function testItReturnsTrueWhenItemBelongsToCollection(): void
    {
        $collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $item       = $this->createMock(IBacklogItem::class);
        $item->method('id')->willReturn(5);
        $collection->push($item);
        self::assertTrue($collection->containsId(5));
    }

    public function testItReturnsFalseWhenItemDoesntBelongToCollection(): void
    {
        $collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $item       = $this->createMock(IBacklogItem::class);
        $item->method('id')->willReturn(5);
        $collection->push($item);
        self::assertFalse($collection->containsId(2));
    }
}
