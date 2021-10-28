<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\TimeboxNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class UriRetrieverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private TimeboxIdentifier $artifact_identifier;
    private UriRetriever $cross_reference_retriever;


    protected function setUp(): void
    {
        $this->artifact_factory          = $this->createStub(Tracker_ArtifactFactory::class);
        $this->cross_reference_retriever = new UriRetriever($this->artifact_factory);
        $this->artifact_identifier       = TimeboxIdentifierStub::withId(1);
    }

    public function testItThrowsWhenArtifactIsNotFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        $this->expectException(TimeboxNotFoundException::class);
        $this->cross_reference_retriever->getUri($this->artifact_identifier);
    }

    public function testItReturnsValue(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getUri')->willReturn('trackers?aid=1');
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        self::assertEquals('trackers?aid=1', $this->cross_reference_retriever->getUri($this->artifact_identifier));
    }
}
