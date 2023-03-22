<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\ArtifactUserCanViewStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

final class StoredReverseLinkTest extends TestCase
{
    private const ARTIFACT_ID = 1045;
    private const TYPE        = '_is_child';
    private StoredLinkRow $row;
    private RetrieveArtifactStub $artifact_retriever;

    protected function setUp(): void
    {
        $this->row = new StoredLinkRow(self::ARTIFACT_ID, self::TYPE);
        $artifact  = ArtifactUserCanViewStub::buildUserCanViewArtifact(self::ARTIFACT_ID);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact);
    }

    private function build(): ?StoredReverseLink
    {
        $user = UserTestBuilder::buildWithDefaults();
        return StoredReverseLink::fromRow($this->artifact_retriever, $user, $this->row);
    }

    public function testItReturnsNullIfTheSourceArtifactCannotBeRetrieved(): void
    {
        $this->artifact_retriever = RetrieveArtifactStub::withNoArtifact();
        self::assertNull($this->build());
    }

    public function testItReturnsNullIfTheUserCannotSeeTheArtifact(): void
    {
        $artifact                 = ArtifactUserCanViewStub::buildUserCannotViewArtifact(self::ARTIFACT_ID);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact);
        self::assertNull($this->build());
    }

    public function testItReturnsTheSourceArtifactAndTheType(): void
    {
        $reverse_link = $this->build();
        self::assertSame(self::ARTIFACT_ID, $reverse_link->getSourceArtifactId());
        self::assertSame(self::TYPE, $reverse_link->getType());
    }

    public function testItDefaultsNullTypeToNoType(): void
    {
        $this->row    = new StoredLinkRow(self::ARTIFACT_ID, null);
        $reverse_link = $this->build();
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $reverse_link->getType());
    }
}
