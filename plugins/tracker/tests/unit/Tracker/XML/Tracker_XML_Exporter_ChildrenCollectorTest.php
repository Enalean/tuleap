<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Exporter_ChildrenCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Tracker_XML_Exporter_ChildrenCollectorTest */
    private $collector;

    protected function setUp(): void
    {
        $this->collector = new Tracker_XML_ChildrenCollector();
    }

    public function testItRaisesAnExceptionWhenTooManyChildrenAreAdded(): void
    {
        $this->expectException(\Tracker_XML_Exporter_TooManyChildrenException::class);
        for ($i = 0; $i <= Tracker_XML_ChildrenCollector::MAX; ++$i) {
            $this->collector->addChild($i, 'whatever');
        }
    }

    public function testItPopsChildren(): void
    {
        $this->collector->addChild(1, 'whatever');
        $this->collector->addChild(2, 'whatever');

        $this->assertEquals(1, $this->collector->pop());
        $this->assertEquals(2, $this->collector->pop());
        $this->assertEquals(null, $this->collector->pop());
    }

    public function testItDoesNotStackTwiceTheSameChild(): void
    {
        $this->collector->addChild(1, 1123);
        $this->collector->addChild(1, 1123);
        $this->assertEquals([1], $this->collector->getAllChildrenIds());
    }

    public function testItReturnsAllParents(): void
    {
        $this->collector->addChild(1, 1123);
        $this->collector->addChild(2, 1123);
        $this->collector->addChild(3, 147);

        $parents_array = [1123, 147];
        $this->assertEquals($parents_array, $this->collector->getAllParents());
    }

    public function testItReturnsChildrenOfAParent(): void
    {
        $parent_id = 1123;
        $this->collector->addChild(1, $parent_id);
        $this->collector->addChild(2, $parent_id);
        $this->collector->addChild(3, 147);

        $children_array = [1, 2];
        $this->assertEquals($children_array, $this->collector->getChildrenForParent($parent_id));
    }

    public function testItReturnsEmptyArrayIfParentNotFound(): void
    {
        $parent_id = 1123;
        $this->collector->addChild(1, $parent_id);
        $this->collector->addChild(2, $parent_id);
        $this->collector->addChild(3, 147);

        $children_array = [];
        $this->assertEquals($children_array, $this->collector->getChildrenForParent(666));
    }

    public function testItAddsTheParentEvenIfChildIsAlreadyStacked(): void
    {
        $this->collector->addChild(1, 1123);
        $this->collector->addChild(1, 1124);
        $this->assertEquals([1], $this->collector->getAllChildrenIds());
        $this->assertEquals([1123, 1124], $this->collector->getAllParents());
    }
}
