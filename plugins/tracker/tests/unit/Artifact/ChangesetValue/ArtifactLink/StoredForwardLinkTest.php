<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StoredForwardLinkTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 53;
    private const TYPE        = 'custom_type';
    private RetrieveArtifactStub $artifact_retriever;
    private array $row;

    protected function setUp(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn(self::ARTIFACT_ID);
        $artifact->method('userCanView')->willReturn(true);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact);

        $this->row = ['artifact_id' => self::ARTIFACT_ID, 'nature' => self::TYPE];
    }

    private function build(): ?StoredForwardLink
    {
        $user = UserTestBuilder::buildWithDefaults();
        return StoredForwardLink::fromRow(
            $this->artifact_retriever,
            $user,
            $this->row
        );
    }

    public function testItBuildsFromDatabaseRow(): void
    {
        $link = $this->build();
        self::assertNotNull($link);
        self::assertSame(self::ARTIFACT_ID, $link->getTargetArtifactId());
        self::assertSame(self::TYPE, $link->getType());
    }

    public function testItDefaultsNullTypeToNoType(): void
    {
        $this->row['nature'] = null;
        $link                = $this->build();
        self::assertNotNull($link);
        self::assertSame(self::ARTIFACT_ID, $link->getTargetArtifactId());
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $link->getType());
    }

    public function testItReturnsNullWhenArtifactCannotBeRetrieved(): void
    {
        $this->artifact_retriever = RetrieveArtifactStub::withNoArtifact();
        self::assertNull($this->build());
    }

    public function testItReturnsNullWhenUserCannotSeeLinkedArtifact(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('userCanView')->willReturn(false);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact);
        self::assertNull($this->build());
    }
}
