<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class ChangeReverseLinksCommandTest extends TestCase
{
    private const FIRST_ARTIFACT_ID  = 976;
    private const SECOND_ARTIFACT_ID = 133;
    private const SECOND_TYPE        = 'custom';
    private CollectionOfReverseLinks $existing_links;
    private CollectionOfReverseLinks $submitted_links;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->artifact        = ArtifactTestBuilder::anArtifact(811)->build();
        $this->existing_links  = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::FIRST_ARTIFACT_ID),
            ReverseLinkStub::withType(self::SECOND_ARTIFACT_ID, self::SECOND_TYPE),
        ]);
        $this->submitted_links = new CollectionOfReverseLinks($this->existing_links->links);
    }

    private function fromSubmittedAndExisting(): ChangeReverseLinksCommand
    {
        return ChangeReverseLinksCommand::fromSubmittedAndExistingLinks(
            $this->artifact,
            $this->submitted_links,
            $this->existing_links
        );
    }

    public function testItBuildsNoChangeWithSubmittedLinksEqualToExistingLinks(): void
    {
        $command = $this->fromSubmittedAndExisting();
        self::assertSame($this->artifact, $command->getTargetArtifact());
        self::assertEmpty($command->getLinksToAdd()->links);
        self::assertEmpty($command->getLinksToChange()->links);
        self::assertEmpty($command->getLinksToRemove()->links);
    }

    public function testItBuildsDiffWithLinksToAdd(): void
    {
        $this->submitted_links = new CollectionOfReverseLinks([
            ...$this->existing_links->links,
            ReverseLinkStub::withNoType(845),
            ReverseLinkStub::withType(251, '_is_child'),
        ]);

        $command = $this->fromSubmittedAndExisting();
        self::assertSame($this->artifact, $command->getTargetArtifact());
        self::assertEmpty($command->getLinksToRemove()->links);
        self::assertEmpty($command->getLinksToChange()->links);

        $added_links = $command->getLinksToAdd()->links;
        self::assertCount(2, $added_links);
        self::assertEqualsCanonicalizing([
            ReverseLinkStub::withNoType(845),
            ReverseLinkStub::withType(251, '_is_child'),
        ], $added_links);
    }

    public function testItBuildsDiffWithLinksToRemove(): void
    {
        $this->submitted_links = new CollectionOfReverseLinks([]);

        $command = $this->fromSubmittedAndExisting();
        self::assertSame($this->artifact, $command->getTargetArtifact());
        self::assertEmpty($command->getLinksToAdd()->links);
        self::assertEmpty($command->getLinksToChange()->links);

        $removed_links = $command->getLinksToRemove()->links;
        self::assertCount(2, $removed_links);
        self::assertEqualsCanonicalizing([
            ReverseLinkStub::withNoType(self::FIRST_ARTIFACT_ID),
            ReverseLinkStub::withType(self::SECOND_ARTIFACT_ID, self::SECOND_TYPE),
        ], $removed_links);
    }

    public function testItBuildsDiffWithChangesOfLinkTypes(): void
    {
        $this->submitted_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(self::FIRST_ARTIFACT_ID, 'fixed_in'),
            ReverseLinkStub::withNoType(self::SECOND_ARTIFACT_ID),
        ]);

        $command = $this->fromSubmittedAndExisting();
        self::assertSame($this->artifact, $command->getTargetArtifact());
        self::assertEmpty($command->getLinksToAdd()->links);
        self::assertEmpty($command->getLinksToRemove()->links);

        $changed_links = $command->getLinksToChange()->links;
        self::assertCount(2, $changed_links);
        self::assertEqualsCanonicalizing([
            ReverseLinkStub::withType(self::FIRST_ARTIFACT_ID, 'fixed_in'),
            ReverseLinkStub::withNoType(self::SECOND_ARTIFACT_ID),
        ], $changed_links);
    }

    public function testItBuildsDiffWithAllAtTheSameTime(): void
    {
        $this->submitted_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(self::SECOND_ARTIFACT_ID, 'fixed_in'),
            ReverseLinkStub::withNoType(687),
        ]);

        $command = $this->fromSubmittedAndExisting();
        self::assertCount(1, $command->getLinksToAdd()->links);
        self::assertCount(1, $command->getLinksToChange()->links);
        self::assertCount(1, $command->getLinksToRemove()->links);
    }
}
