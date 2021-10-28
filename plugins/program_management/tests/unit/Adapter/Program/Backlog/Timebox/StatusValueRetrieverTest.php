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

use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_List;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class StatusValueRetrieverTest extends TestCase
{
    private RetrieveUserStub $retrieve_user;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker_FormElement_Field_List
     */
    private $status_field;
    private TimeboxIdentifier $artifact_identifier;
    private UserIdentifier $user_identifier;
    private StatusValueRetriever $status_value_retriever;


    protected function setUp(): void
    {
        $this->artifact_factory = $this->createStub(Tracker_ArtifactFactory::class);
        $this->retrieve_user    = RetrieveUserStub::withGenericUser();

        $this->status_value_retriever = new StatusValueRetriever($this->artifact_factory, $this->retrieve_user);

        $this->status_field = $this->createStub(Tracker_FormElement_Field_List::class);

        $this->tracker = $this->createStub(Tracker::class);

        $this->artifact = $this->createStub(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);

        $this->artifact_identifier = TimeboxIdentifierStub::withId(1);
        $this->user_identifier     = UserIdentifierStub::buildGenericUser();
    }

    public function testItReturnsNullWhenArtifactIsNotFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        self::assertNull($this->status_value_retriever->getLabel($this->artifact_identifier, $this->user_identifier));
    }

    public function testItReturnsNullWhenStatusFieldIsNotFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->tracker->method('getStatusField')->willReturn(null);

        self::assertNull($this->status_value_retriever->getLabel($this->artifact_identifier, $this->user_identifier));
    }

    public function testItReturnsNullWhenUserCanNotReadField(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->tracker->method('getStatusField')->willReturn($this->status_field);
        $this->status_field->method('userCanRead')->willReturn(false);

        self::assertNull($this->status_value_retriever->getLabel($this->artifact_identifier, $this->user_identifier));
    }

    public function testItReturnsValue(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->tracker->method('getStatusField')->willReturn($this->status_field);
        $this->status_field->method('userCanRead')->willReturn(true);
        $this->artifact->method('getStatus')->willReturn("On going");

        self::assertEquals("On going", $this->status_value_retriever->getLabel($this->artifact_identifier, $this->user_identifier));
    }
}
