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

final class BacklogItemPresenterCollectionTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItReturnsFalseWhenCollectionIsEmpty(): void
    {
        $collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->assertFalse($collection->containsId(5));
    }

    public function testItReturnsTrueWhenItemBelongsToCollection(): void
    {
        $collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $item       = Mockery::mock(AgileDashboard_Milestone_Backlog_IBacklogItem::class);
        $item->shouldReceive('id')->andReturn(5);
        $collection->push($item);
        $this->assertTrue($collection->containsId(5));
    }

    public function testItReturnsFalseWhenItemDoesntBelongToCollection(): void
    {
        $collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $item       = Mockery::mock(AgileDashboard_Milestone_Backlog_IBacklogItem::class);
        $item->shouldReceive('id')->andReturn(5);
        $collection->push($item);
        $this->assertFalse($collection->containsId(2));
    }
}
