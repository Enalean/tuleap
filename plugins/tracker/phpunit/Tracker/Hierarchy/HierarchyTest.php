<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

final class Tracker_HierarchyTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    public function testWithEmptyHierarchyGetLevelShouldThrowExceptionForAnyTracker(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $this->expectException(\Tracker_Hierarchy_NotInHierarchyException::class);
        $hierarchy->getLevel(1);
    }

    public function testGetLevelShouldReturn0ForTheTrackerWithId112(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $level = $hierarchy->getLevel(112);
        $this->assertEquals(0, $level);
    }

    public function testGetLevelShouldReturn1ForTheTrackerWithId111(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $level = $hierarchy->getLevel(111);
        $this->assertEquals(1, $level);
    }

    public function testWhenConstructedWithArrayOfArrayReverseSouldReturn0(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $level = $hierarchy->getLevel(111);
        $this->assertEquals(0, $level);
    }

    public function testWithMultilevelHierarchyGetLevelShouldReturn2(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(113, 111);
        $level = $hierarchy->getLevel(112);
        $this->assertEquals(2, $level);
    }

    public function testGetLevelShouldRaiseAnExceptionIfTheHierarchyIsCyclic(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(113, 112);
        $this->expectException(\Tracker_Hierarchy_CyclicHierarchyException::class);
        $hierarchy->getLevel(111);
    }

    public function testChildCannotBeItsParent(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 111);
        $this->expectException(\Tracker_Hierarchy_CyclicHierarchyException::class);
        $hierarchy->getLevel(111);
    }

    public function testGetLevelShouldReturnOForEachRoots(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(1002, 1050);
        $level = $hierarchy->getLevel(112);
        $this->assertEquals(0, $level);
        $level = $hierarchy->getLevel(1002);
        $this->assertEquals(0, $level);
    }

    public function testItIsRootWhenItIsTheOnlyParent(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertTrue($hierarchy->isRoot(112));
        $this->assertFalse($hierarchy->isRoot(111));
    }

    public function testItIsRootWhenThereAreSeveralParents(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(111, 110);
        $this->assertTrue($hierarchy->isRoot(112));
        $this->assertFalse($hierarchy->isRoot(111));
        $this->assertFalse($hierarchy->isRoot(110));
    }

    public function testIsRootShouldReturnFalseWhenTrackerIsNotInHierarchy(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertFalse($hierarchy->isRoot(666));
    }

    public function testItShouldBeChild(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertTrue($hierarchy->isChild(112, 111));
    }

    public function testItShouldNotBeChild(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertFalse($hierarchy->isChild(112, 666));
        $this->assertFalse($hierarchy->isChild(111, 112));
    }

    public function testItFlattenTheInternalHierarchy(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(112, 113);

        $this->assertEquals([111, 112, 113], $hierarchy->flatten());
    }

    public function testItFlattenTheInternalHierarchyButLonelyTrackerIsAlone(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 0);
        $this->assertEquals([111], $hierarchy->flatten());
    }

    public function testItReturnsParent(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(112, 113);

        $this->assertEquals(null, $hierarchy->getParent(111));
        $this->assertEquals(111, $hierarchy->getParent(112));
        $this->assertEquals(112, $hierarchy->getParent(113));
    }

    public function testItReturnsNullWhenNoHierarchy(): void
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->assertEquals(null, $hierarchy->getParent(111));
    }

    public function testItReturnsEmptyArrayWhenNoHierarchy(): void
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->assertEquals([], $hierarchy->sortTrackerIds([]));
    }

    public function testItReturnsTheTwoTrackersWhenJustOneRelationShip(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 113);
        $this->assertEquals([112, 113], $hierarchy->sortTrackerIds([113, 112]));
    }

    public function testItReturnsTrackersFromTopToBottom(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(111, 112);

        $this->assertEquals([111, 112, 113], $hierarchy->sortTrackerIds([112, 113, 111]));
    }

    public function testItReturnsTrackersFromTopToBottomAndTrackerNotInHierarchyAtTheEnd(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(111, 112);

        // Sorts in PHP are not expected to be stable (https://bugs.php.net/bug.php?id=69158)
        // and since trackers 666, 667 and 668 are not hierarchy
        // they can be placed at any positions at the end
        // That means we can have something like [111, 112, 113, 667, 668, 666]
        // or [111, 112, 113, 668, 666, 667] or …
        $sorted_tracker_ids = $hierarchy->sortTrackerIds([667, 111, 666, 112, 668, 113]);
        $this->assertEquals([111, 112, 113], array_slice($sorted_tracker_ids, 0, 3));
        foreach ([666, 667, 668] as $not_in_hierarchy_tracker_id) {
            $this->assertContains(
                $not_in_hierarchy_tracker_id,
                array_slice($sorted_tracker_ids, 3, 3)
            );
        }
    }

    public function testItReturnsAnEpmtyArrayIfNoRelationshipsExist(): void
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->assertEquals([], $hierarchy->getLastLevelTrackerIds());
    }

    public function testItReturnsLastLevelTrackerIds(): void
    {
        $hierarchy = new Tracker_Hierarchy();

        $grand_pa = 11;
        $papa     = 222;
        $child    = 3333;

        $grand_uncle = 55;
        $uncle       = 444;

        $hierarchy->addRelationship($grand_pa, $papa);
        $hierarchy->addRelationship($grand_pa, $uncle);
        $hierarchy->addRelationship($papa, $child);
        $hierarchy->addRelationship(null, $grand_uncle);

        $expected = [$grand_uncle, $uncle, $child];
        $result   = $hierarchy->getLastLevelTrackerIds();

        sort($expected);
        sort($result);

        $this->assertEquals($expected, $result);
    }
}
