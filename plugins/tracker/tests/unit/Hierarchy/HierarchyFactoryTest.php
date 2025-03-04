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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_HierarchyFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testItReturnsItselfWhenThereIsNoHierarchyAssociatedToTracker(): void
    {
        $factory = $this->givenAHierarchyFactory();

        $lonely_tracker                = [115];
        $lonely_tracker_hierarchy_list = $factory->getHierarchy($lonely_tracker)->flatten();

        $this->assertEquals($lonely_tracker, $lonely_tracker_hierarchy_list);
    }

    public function testItRetrievesTheChildrenOfAGivenTracker(): void
    {
        $hierarchy_dao        = $this->createMock(HierarchyDAO::class);
        $tracker_factory      = $this->createMock(TrackerFactory::class);
        $child_link_retriever = $this->createMock(TypeIsChildLinkRetriever::class);
        $hierarchy_factory    = new Tracker_HierarchyFactory(
            $hierarchy_dao,
            $tracker_factory,
            $this->createMock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );

        $tracker_id = 1;
        $child_ids  = [['id' => 11], ['id' => 12]];

        $child_1 = TrackerTestBuilder::aTracker()->build();
        $child_2 = TrackerTestBuilder::aTracker()->build();

        $hierarchy_dao->method('searchChildTrackerIds')->with($tracker_id)->willReturn($child_ids);
        $tracker_factory->method('getTrackerById')->willReturnCallback(static fn ($id) => match ($id) {
            11 => $child_1,
            12 => $child_2,
        });

        $expected_children = [$child_1, $child_2];
        $actual_children   = $hierarchy_factory->getChildren($tracker_id);

        $this->assertEquals($expected_children, $actual_children);
    }

    public function testItRetrievesTheChildrenOfAGivenTrackerThatAreNotDeleted(): void
    {
        $hierarchy_dao        = $this->createMock(HierarchyDAO::class);
        $tracker_factory      = $this->createMock(TrackerFactory::class);
        $child_link_retriever = $this->createMock(TypeIsChildLinkRetriever::class);
        $hierarchy_factory    = new Tracker_HierarchyFactory(
            $hierarchy_dao,
            $tracker_factory,
            $this->createMock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );

        $tracker_id = 1;
        $child_ids  = [['id' => 11], ['id' => 12]];

        $child_1 = TrackerTestBuilder::aTracker()->build();
        $child_2 = TrackerTestBuilder::aTracker()->withDeletionDate(1234567890)->build();

        $hierarchy_dao->method('searchChildTrackerIds')->with($tracker_id)->willReturn($child_ids);
        $tracker_factory->method('getTrackerById')->willReturnCallback(static fn ($id) => match ($id) {
            11 => $child_1,
            12 => $child_2,
        });

        $expected_children = [$child_1];
        $actual_children   = $hierarchy_factory->getChildren($tracker_id);

        $this->assertEquals($expected_children, $actual_children);
    }

    public function testFactoryShouldCreateAHierarchy(): void
    {
        $factory = $this->givenAHierarchyFactory();
        $this->assertInstanceOf(Tracker_Hierarchy::class, $factory->getHierarchy());
    }

    public function testFactoryShouldReturnManyDifferentHierarchies(): void
    {
        $factory = $this->givenAHierarchyFactory();

        $h1 = $factory->getHierarchy();
        $h2 = $factory->getHierarchy();

        $this->assertTrue($h1 !== $h2);
    }

    public function testFactoryShouldCallTheDatabaseToBuildHierarchy(): void
    {
        $dao = $this->createMock(HierarchyDAO::class);
        $dao->expects(self::once())->method('searchTrackerHierarchy')->willReturn([]);

        $factory = $this->givenAHierarchyFactory($dao);
        $factory->getHierarchy([111]);
    }

    public function testFactoryShouldReturnARealHierarchyAccordingToDatabase(): void
    {
        $dao = $this->createMock(HierarchyDAO::class);
        $dao->method('searchTrackerHierarchy')->willReturn([['parent_id' => 111, 'child_id' => 112]]);

        $factory = $this->givenAHierarchyFactory($dao);

        $hierarchy = $factory->getHierarchy([111]);
        $this->assertEquals(1, $hierarchy->getLevel(112));
    }

    public function testFactoryShouldReturnFullHierarchy(): void
    {
        /*
          111
          +- 112
             +- 113
                +- 114
        */
        $dao     = $this->givenADaoThatContainsFullHierarchy();
        $factory = $this->givenAHierarchyFactory($dao);

        $hierarchy = $factory->getHierarchy([111, 114]);
        $this->assertEquals(3, $hierarchy->getLevel(114));
    }

    public function testDuplicateHierarchy(): void
    {
        $dao = $this->givenADaoThatContainsOneFullHierarchy();

        $factory = $this->givenAHierarchyFactory($dao);

        $tracker_mapping = [
            '111' => '211',
            '112' => '212',
            '113' => '213',
            '114' => '214',
        ];

        $dao->expects(self::exactly(3))->method('duplicate');

        $factory->duplicate($tracker_mapping);
    }

    private function givenADaoThatContainsOneFullHierarchy(): HierarchyDAO&MockObject
    {
        $dao = $this->createMock(HierarchyDAO::class);
        $dar = [
            ['parent_id' => 111, 'child_id' => 112],
            ['parent_id' => 112, 'child_id' => 113],
            ['parent_id' => 113, 'child_id' => 114],
        ];
        $dao->method('searchTrackerHierarchy')->with([111, 112, 113, 114])->willReturn($dar);

        return $dao;
    }

    private function givenADaoThatContainsFullHierarchy(): HierarchyDAO
    {
        $dar1 = [
            ['parent_id' => 111, 'child_id' => 112],
            ['parent_id' => 113, 'child_id' => 114],
        ];
        $dar2 = [
            ['parent_id' => 111, 'child_id' => 112],
            ['parent_id' => 112, 'child_id' => 113],
            ['parent_id' => 113, 'child_id' => 114],
        ];

        $dao = $this->createMock(HierarchyDAO::class);
        $dao->method('searchTrackerHierarchy')
            ->willReturnCallback(static fn (array $tracker_ids) => match ($tracker_ids) {
                [111, 114] => $dar1,
                [112, 113] => $dar2,
            });

        return $dao;
    }

    private function givenAHierarchyFactory(?HierarchyDAO $dao = null): Tracker_HierarchyFactory
    {
        if (! $dao) {
            $dao = $this->createMock(HierarchyDAO::class);
            $dao->method('searchTrackerHierarchy')->willReturn([]);
        }
        $child_link_retriever = $this->createMock(TypeIsChildLinkRetriever::class);

        return new Tracker_HierarchyFactory(
            $dao,
            $this->createMock(TrackerFactory::class),
            $this->createMock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );
    }
}
