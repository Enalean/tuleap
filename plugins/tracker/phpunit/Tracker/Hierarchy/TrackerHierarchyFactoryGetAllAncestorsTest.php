<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

final class TrackerHierarchyFactoryGetAllAncestorsTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $release;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $product;
    /**
     * @var \Mockery\Mock | Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $sprint;

    protected function setUp(): void
    {
        $this->user = new PFUser(['language_id' => 'en']);
        $this->hierarchy_factory = Mockery::mock(Tracker_HierarchyFactory::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturnFalse();

        $this->sprint = Mockery::mock(Tracker_Artifact::class);
        $this->sprint->shouldReceive('getId')->andReturn(101);
        $this->product = Mockery::mock(Tracker_Artifact::class);
        $this->product->shouldReceive('getId')->andReturn(103);
        $this->product->shouldReceive('getTracker')->andReturn($tracker);
        $this->release = Mockery::mock(Tracker_Artifact::class);
        $this->release->shouldReceive('getId')->andReturn(102);
        $this->release->shouldReceive('getTracker')->andReturn($tracker);
    }

    public function testItReturnsEmptyArrayWhenNoAncestors(): void
    {
        $this->getParentOfArtifact($this->sprint, null);

        $this->assertEquals([], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsTheParentWhenThereIsOnlyOne(): void
    {
        $this->getParentOfArtifact($this->sprint, $this->release);
        $this->getParentOfArtifact($this->release, null);

        $this->assertEquals([$this->release], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsNothingWhenChildReferenceItselfAsParent(): void
    {
        $this->getParentOfArtifact($this->sprint, $this->sprint);

        $this->assertEquals([], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsParentsOnlyOnceWhenTheParentReferenceItself(): void
    {
        $this->getParentOfArtifact($this->sprint, $this->release);
        $this->getParentOfArtifact($this->release, $this->release);

        $this->assertEquals([$this->release], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsParentsOnlyOnceWhenThereIsACycleBetweenParents(): void
    {
        $this->getParentOfArtifact($this->sprint, $this->release);
        $this->getParentOfArtifact($this->release, $this->product);
        $this->getParentOfArtifact($this->product, $this->release);

        $this->assertEquals([$this->release, $this->product], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsParentsOnlyOnceWhenThereIsAFullCycle(): void
    {
        $this->getParentOfArtifact($this->sprint, $this->release);
        $this->getParentOfArtifact($this->release, $this->product);
        $this->getParentOfArtifact($this->sprint, $this->product);
        $this->getParentOfArtifact($this->product, null);

        $this->assertEquals([$this->release, $this->product], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsSeveralParents(): void
    {
        $this->getParentOfArtifact($this->sprint, $this->release);
        $this->getParentOfArtifact($this->release, $this->product);
        $this->getParentOfArtifact($this->product, null);

        $this->assertEquals([$this->release, $this->product], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    /**
     * @param \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact $artifact
     * @param \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact|null $parent
     */
    private function getParentOfArtifact($artifact, $parent): void
    {
        $this->hierarchy_factory->shouldReceive('getParentArtifact')
            ->with($this->user, $artifact)->andReturn($parent);
    }
}
