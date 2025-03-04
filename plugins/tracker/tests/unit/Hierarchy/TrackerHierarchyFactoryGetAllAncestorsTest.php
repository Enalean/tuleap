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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerHierarchyFactoryGetAllAncestorsTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private Artifact $release;
    private Artifact $product;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;
    private PFUser $user;
    private Artifact $sprint;

    protected function setUp(): void
    {
        $this->user              = new PFUser(['language_id' => 'en']);
        $this->hierarchy_factory = $this->getMockBuilder(Tracker_HierarchyFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParentArtifact'])
            ->getMock();

        $tracker = TrackerTestBuilder::aTracker()->build();

        $this->sprint  = ArtifactTestBuilder::anArtifact(101)->build();
        $this->release = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();
        $this->product = ArtifactTestBuilder::anArtifact(103)->inTracker($tracker)->build();
    }

    public function testItReturnsEmptyArrayWhenNoAncestors(): void
    {
        $this->hierarchy_factory->method('getParentArtifact')->willReturn(null);

        $this->assertEquals([], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsTheParentWhenThereIsOnlyOne(): void
    {
        $this->hierarchy_factory
            ->method('getParentArtifact')
            ->willReturnCallback(fn (PFUser $user, Artifact $artifact) => match ($artifact) {
                $this->sprint  => $this->release,
                $this->release => null,
            });

        $this->assertEquals([$this->release], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsNothingWhenChildReferenceItselfAsParent(): void
    {
        $this->hierarchy_factory
            ->method('getParentArtifact')
            ->willReturnCallback(fn (PFUser $user, Artifact $artifact) => match ($artifact) {
                $this->sprint => $this->sprint,
            });

        $this->assertEquals([], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsParentsOnlyOnceWhenTheParentReferenceItself(): void
    {
        $this->hierarchy_factory
            ->method('getParentArtifact')
            ->willReturnCallback(fn (PFUser $user, Artifact $artifact) => match ($artifact) {
                $this->sprint  => $this->release,
                $this->release => $this->release,
            });

        $this->assertEquals([$this->release], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsParentsOnlyOnceWhenThereIsACycleBetweenParents(): void
    {
        $this->hierarchy_factory
            ->method('getParentArtifact')
            ->willReturnCallback(fn (PFUser $user, Artifact $artifact) => match ($artifact) {
                $this->sprint  => $this->release,
                $this->release  => $this->product,
                $this->product  => $this->release,
            });

        $this->assertEquals([$this->release, $this->product], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }

    public function testItReturnsSeveralParents(): void
    {
        $this->hierarchy_factory
            ->method('getParentArtifact')
            ->willReturnCallback(fn (PFUser $user, Artifact $artifact) => match ($artifact) {
                $this->sprint  => $this->release,
                $this->release  => $this->product,
                $this->product  => null,
            });

        $this->assertEquals([$this->release, $this->product], $this->hierarchy_factory->getAllAncestors($this->user, $this->sprint));
    }
}
