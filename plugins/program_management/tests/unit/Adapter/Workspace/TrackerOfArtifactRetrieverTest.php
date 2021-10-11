<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\ArtifactNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerOfArtifactRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 550;
    private const TRACKER_ID  = 908;
    /**
     * @var Stub&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Stub&\TrackerFactory
     */
    private $tracker_factory;
    private ArtifactIdentifierStub $artifact;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->tracker_factory  = $this->createStub(\TrackerFactory::class);

        $this->artifact = ArtifactIdentifierStub::withId(self::ARTIFACT_ID);
    }

    private function getRetriever(): TrackerOfArtifactRetriever
    {
        return new TrackerOfArtifactRetriever(
            $this->artifact_factory,
            $this->tracker_factory
        );
    }

    public function testItReturnsTrackerOfArtifact(): void
    {
        $project      = ProjectTestBuilder::aProject()->withId(135)->build();
        $full_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject($project)
            ->withName('Frontingly')
            ->build();
        $artifact     = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->inTracker($full_tracker)
            ->build();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
        $this->tracker_factory->method('getTrackerById')->willReturn($full_tracker);

        $tracker = $this->getRetriever()->getTrackerOfArtifact($this->artifact);
        self::assertSame(self::TRACKER_ID, $tracker->getId());
    }

    public function testItThrowsWhenTrackerCantBeFound(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(723)->build())
            ->build();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->expectException(TrackerNotFoundException::class);
        $this->getRetriever()->getTrackerOfArtifact($this->artifact);
    }

    public function testItThrowsWhenArtifactCantBeFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(ArtifactNotFoundException::class);
        $this->getRetriever()->getTrackerOfArtifact($this->artifact);
    }
}
