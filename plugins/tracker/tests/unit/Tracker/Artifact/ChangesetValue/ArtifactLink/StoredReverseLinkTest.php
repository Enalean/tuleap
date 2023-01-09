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

use PFUser;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\ArtifactUserCanViewStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

final class StoredReverseLinkTest extends TestCase
{
    private PFUser $user;
    private StoredLinkRow $row;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();
        $this->row  = new StoredLinkRow(15, "_is_child");
    }

    public function testItReturnsNullIfTheSourceArtifactCannotBeRetrieved(): void
    {
        $artifact_retriever = RetrieveArtifactStub::withNoArtifact();

        self::assertNull(StoredReverseLink::fromRow($artifact_retriever, $this->user, $this->row));
    }

    public function testItReturnsNullIfTheUserCannotSeeTheArtifact(): void
    {
        $artifact = ArtifactUserCanViewStub::buildUserCannotViewArtifact(12);

        $artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact);

        self::assertNull(StoredReverseLink::fromRow($artifact_retriever, $this->user, $this->row));
    }

    public function testItReturnsTheSourceArtifactAndTheType(): void
    {
        $artifact =  ArtifactUserCanViewStub::buildUserCanViewArtifact(1045);

        $artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact);

        $reverse_link = StoredReverseLink::fromRow($artifact_retriever, $this->user, $this->row);

        self::assertSame(1045, $reverse_link->getSourceArtifactId());
        self::assertSame("_is_child", $reverse_link->getType());
    }
}
