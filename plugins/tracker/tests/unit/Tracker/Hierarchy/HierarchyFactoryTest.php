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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class Tracker_HierarchyFactoryTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItReturnsItselfWhenThereIsNoHierarchyAssociatedToTracker(): void
    {
        $factory = $this->givenAHierarchyFactory();

        $lonely_tracker                = [115];
        $lonely_tracker_hierarchy_list = $factory->getHierarchy($lonely_tracker)->flatten();

        $this->assertEquals($lonely_tracker, $lonely_tracker_hierarchy_list);
    }

    public function testItRetrievesTheChildrenOfAGivenTracker(): void
    {
        $hierarchy_dao        = Mockery::mock(HierarchyDAO::class);
        $tracker_factory      = Mockery::mock(TrackerFactory::class);
        $child_link_retriever = Mockery::mock(NatureIsChildLinkRetriever::class);
        $hierarchy_factory    = new Tracker_HierarchyFactory(
            $hierarchy_dao,
            $tracker_factory,
            Mockery::mock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );

        $tracker_id = 1;
        $child_ids  = [['id' => 11], ['id' => 12]];

        $child_1 = Mockery::mock(Tracker::class);
        $child_2 = Mockery::mock(Tracker::class);

        $hierarchy_dao->shouldReceive('searchChildTrackerIds')->with($tracker_id)->andReturn($child_ids);
        $tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($child_1);
        $tracker_factory->shouldReceive('getTrackerById')->with(12)->andReturn($child_2);

        $expected_children = [$child_1, $child_2];
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
        $dao = Mockery::mock(HierarchyDAO::class);
        $dao->shouldReceive('searchTrackerHierarchy')->andReturn([])->once();

        $factory = $this->givenAHierarchyFactory($dao);
        $factory->getHierarchy([111]);
    }

    public function testFactoryShouldReturnARealHierarchyAccordingToDatabase(): void
    {
        $dao = Mockery::mock(HierarchyDAO::class);
        $dao->shouldReceive('searchTrackerHierarchy')->andReturn([['parent_id' => 111, 'child_id' => 112]]);

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

        $dao->shouldReceive('duplicate')->times(3);

        $factory->duplicate($tracker_mapping);
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|HierarchyDAO
     */
    private function givenADaoThatContainsOneFullHierarchy()
    {
        $dao = Mockery::mock(HierarchyDAO::class);
        $dar = [
            ['parent_id' => 111, 'child_id' => 112],
            ['parent_id' => 112, 'child_id' => 113],
            ['parent_id' => 113, 'child_id' => 114]
        ];
        $dao->shouldReceive('searchTrackerHierarchy')->with([111, 112, 113, 114])->andReturn($dar);

        return $dao;
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|HierarchyDAO
     */
    private function givenADaoThatContainsFullHierarchy()
    {
        $dao     = Mockery::mock(HierarchyDAO::class);
        $dar1 = [
            array('parent_id' => 111, 'child_id' => 112),
            array('parent_id' => 113, 'child_id' => 114)
        ];
        $dao->shouldReceive('searchTrackerHierarchy')->with([111, 114])->andReturn($dar1);
        $dar2 = [
            array('parent_id' => 111, 'child_id' => 112),
            array('parent_id' => 112, 'child_id' => 113),
            array('parent_id' => 113, 'child_id' => 114)
         ];
        $dao->shouldReceive('searchTrackerHierarchy')->with([112, 113])->andReturn($dar2);
        return $dao;
    }

    private function givenAHierarchyFactory($dao = null): \Tracker_HierarchyFactory
    {
        if (! $dao) {
            $dao = Mockery::mock(HierarchyDAO::class);
            $dao->shouldReceive('searchTrackerHierarchy')->andReturn([]);
        }
        $child_link_retriever = Mockery::mock(NatureIsChildLinkRetriever::class);

        return new Tracker_HierarchyFactory(
            $dao,
            Mockery::mock(TrackerFactory::class),
            Mockery::mock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );
    }
}
