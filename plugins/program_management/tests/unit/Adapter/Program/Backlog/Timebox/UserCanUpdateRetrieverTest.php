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
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class UserCanUpdateRetrieverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private TimeboxIdentifier $artifact_identifier;
    private UserCanUpdateRetriever $cross_reference_retriever;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->artifact_factory          = $this->createStub(Tracker_ArtifactFactory::class);
        $this->cross_reference_retriever = new UserCanUpdateRetriever($this->artifact_factory, RetrieveUserStub::withGenericUser());
        $this->artifact_identifier       = TimeboxIdentifierStub::withId(1);
        $this->user_identifier           = UserIdentifierStub::buildGenericUser();
    }

    public function testItThrowsWhenArtifactIsNotFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        $this->expectException(TimeboxNotFoundException::class);
        $this->cross_reference_retriever->userCanUpdate($this->artifact_identifier, $this->user_identifier);
    }

    public function testItReturnsValue(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('userCanUpdate')->willReturn(true);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        self::assertEquals(true, $this->cross_reference_retriever->userCanUpdate($this->artifact_identifier, $this->user_identifier));
    }
}
