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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeForwardLinksCommand;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;

final class ChangeForwardLinksCommandTest extends TestCase
{
    private const FIELD_ID           = 939;
    private const FIRST_ARTIFACT_ID  = 811;
    private const SECOND_ARTIFACT_ID = 611;
    private const SECOND_TYPE        = '_is_child';
    private CollectionOfForwardLinks $existing_links;
    /**
     * @var Option<CollectionOfForwardLinks> $submitted_links
     */
    private Option $submitted_links;

    protected function setUp(): void
    {
        $this->existing_links  = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::FIRST_ARTIFACT_ID),
            ForwardLinkStub::withType(self::SECOND_ARTIFACT_ID, self::SECOND_TYPE),
        ]);
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks($this->existing_links->getArtifactLinks())
        );
    }

    private function fromSubmittedAndExisting(): ChangeForwardLinksCommand
    {
        return ChangeForwardLinksCommand::fromSubmittedAndExistingLinks(
            self::FIELD_ID,
            $this->submitted_links,
            $this->existing_links
        );
    }

    public function testItBuildsNoChangeWithSubmittedLinksEqualToExistingLinks(): void
    {
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks($this->existing_links->getArtifactLinks())
        );

        $command = $this->fromSubmittedAndExisting();
        self::assertSame(self::FIELD_ID, $command->getFieldId());
        self::assertEmpty($command->getLinksToAdd()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToChange()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToRemove()->getTargetArtifactIds());
    }

    public function testItBuildsNoChangeWithNoSubmittedLinks(): void
    {
        $this->submitted_links = Option::nothing(CollectionOfForwardLinks::class);

        $command = $this->fromSubmittedAndExisting();
        self::assertSame(self::FIELD_ID, $command->getFieldId());
        self::assertEmpty($command->getLinksToAdd()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToChange()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToRemove()->getTargetArtifactIds());
    }

    public function testItBuildsDiffWithLinksToAdd(): void
    {
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks([
                ...$this->existing_links->getArtifactLinks(),
                ForwardLinkStub::withType(971, 'custom'),
                ForwardLinkStub::withNoType(129),
            ])
        );

        $command = $this->fromSubmittedAndExisting();
        self::assertSame(self::FIELD_ID, $command->getFieldId());
        self::assertEmpty($command->getLinksToRemove()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToChange()->getTargetArtifactIds());

        $added_links = $command->getLinksToAdd()->getArtifactLinks();
        self::assertCount(2, $added_links);
        self::assertEqualsCanonicalizing([
            ForwardLinkStub::withType(971, 'custom'),
            ForwardLinkStub::withNoType(129),
        ], $added_links);
    }

    public function testItBuildsDiffWithLinksToRemove(): void
    {
        $this->submitted_links = Option::fromValue(new CollectionOfForwardLinks([]));

        $command = $this->fromSubmittedAndExisting();
        self::assertSame(self::FIELD_ID, $command->getFieldId());
        self::assertEmpty($command->getLinksToAdd()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToChange()->getTargetArtifactIds());

        $removed_links = $command->getLinksToRemove()->getArtifactLinks();
        self::assertCount(2, $removed_links);
        self::assertEqualsCanonicalizing([
            ForwardLinkStub::withNoType(self::FIRST_ARTIFACT_ID),
            ForwardLinkStub::withType(self::SECOND_ARTIFACT_ID, self::SECOND_TYPE),
        ], $removed_links);
    }

    public function testItBuildsDiffWithChangesOfLinkTypes(): void
    {
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks([
                ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, 'custom'),
                ForwardLinkStub::withNoType(self::SECOND_ARTIFACT_ID),
            ])
        );

        $command = $this->fromSubmittedAndExisting();
        self::assertSame(self::FIELD_ID, $command->getFieldId());
        self::assertEmpty($command->getLinksToAdd()->getTargetArtifactIds());
        self::assertEmpty($command->getLinksToRemove()->getTargetArtifactIds());

        $changed_links = $command->getLinksToChange()->getArtifactLinks();
        self::assertCount(2, $changed_links);
        self::assertEqualsCanonicalizing([
            ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, 'custom'),
            ForwardLinkStub::withNoType(self::SECOND_ARTIFACT_ID),
        ], $changed_links);
    }

    public function testItBuildsDiffWithAllAtTheSameTime(): void
    {
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks([
                ForwardLinkStub::withType(self::SECOND_ARTIFACT_ID, 'custom'),
                ForwardLinkStub::withNoType(960),
            ])
        );

        $command = $this->fromSubmittedAndExisting();
        self::assertCount(1, $command->getLinksToAdd()->getTargetArtifactIds());
        self::assertCount(1, $command->getLinksToChange()->getTargetArtifactIds());
        self::assertCount(1, $command->getLinksToRemove()->getTargetArtifactIds());
    }

    public function testItBuildsFromParts(): void
    {
        $links_to_add    = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(775),
            ForwardLinkStub::withType(153, '_is_child'),
        ]);
        $links_to_change = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(670, 'custom'),
        ]);
        $links_to_remove = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(72),
            ForwardLinkStub::withType(413, '_is_child'),
        ]);

        $command = ChangeForwardLinksCommand::fromParts(
            self::FIELD_ID,
            $links_to_add,
            $links_to_change,
            $links_to_remove,
        );

        self::assertSame(self::FIELD_ID, $command->getFieldId());
        self::assertSame($links_to_add, $command->getLinksToAdd());
        self::assertSame($links_to_change, $command->getLinksToChange());
        self::assertSame($links_to_remove, $command->getLinksToRemove());
    }
}
