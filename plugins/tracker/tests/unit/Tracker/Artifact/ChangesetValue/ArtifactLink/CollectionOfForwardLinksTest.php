<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class CollectionOfForwardLinksTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ARTIFACT_ID  = 804;
    private const FIRST_TYPE         = '_is_child';
    private const SECOND_ARTIFACT_ID = 955;
    private const SECOND_TYPE        = '_depends_on';
    private const THIRD_ARTIFACT_ID  = 103;
    /**
     * @var ForwardLinkStub[]
     */
    private array $artifact_links;

    protected function setUp(): void
    {
        $this->artifact_links = [
            ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, self::FIRST_TYPE),
            ForwardLinkStub::withType(self::SECOND_ARTIFACT_ID, self::SECOND_TYPE),
            ForwardLinkStub::withNoType(self::THIRD_ARTIFACT_ID),
        ];
    }

    public function testItReturnsItsArtifactLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);

        self::assertEqualsCanonicalizing($this->artifact_links, $collection->getArtifactLinks());
    }

    public function testItReturnsTheIdsOfItsArtifactLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);

        self::assertEqualsCanonicalizing(
            [self::FIRST_ARTIFACT_ID, self::SECOND_ARTIFACT_ID, self::THIRD_ARTIFACT_ID],
            $collection->getTargetArtifactIds()
        );
    }

    public function testItReturnsTheTypesByArtifactLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);

        self::assertEqualsCanonicalizing([
            self::FIRST_ARTIFACT_ID  => '_is_child',
            self::SECOND_ARTIFACT_ID => self::SECOND_TYPE,
            self::THIRD_ARTIFACT_ID  => \Tracker_FormElement_Field_ArtifactLink::NO_TYPE,
        ], $collection->getArtifactTypesByIds());
    }

    public function testItBuildsWithASingleLinkFromAReverseLinkAndASourceArtifact(): void
    {
        $source       = ArtifactTestBuilder::anArtifact(378)->build();
        $reverse_link = ReverseLinkStub::withType(662, '_is_child');
        $collection   = CollectionOfForwardLinks::fromReverseLink($source, $reverse_link);
        self::assertEqualsCanonicalizing([
            378 => '_is_child',
        ], $collection->getArtifactTypesByIds());
    }

    public function testItReturnsADiffOfAddedAndRemovedLinks(): void
    {
        $current_forward_links = new CollectionOfForwardLinks($this->artifact_links);
        $submitted_links       = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, self::FIRST_TYPE),
            ForwardLinkStub::withType(self::SECOND_ARTIFACT_ID, '_is_child'),
            ForwardLinkStub::withType(104, '_is_child'),
        ]);

        $added_collection   = $current_forward_links->differenceById($submitted_links);
        $removed_collection = $submitted_links->differenceById($current_forward_links);

        $new_values = $added_collection->getTargetArtifactIds();
        self::assertCount(1, $new_values);
        self::assertContains(104, $new_values);
        $removed_values = $removed_collection->getTargetArtifactIds();
        self::assertCount(1, $removed_values);
        self::assertContains(self::THIRD_ARTIFACT_ID, $removed_values);
    }

    public function testItReturnsAnEmptyDiffWhenThereIsNoChange(): void
    {
        $submitted_links       = new CollectionOfForwardLinks($this->artifact_links);
        $current_forward_links = new CollectionOfForwardLinks($this->artifact_links);

        $added_collection   = $current_forward_links->differenceById($submitted_links);
        $removed_collection = $submitted_links->differenceById($current_forward_links);

        self::assertEmpty($added_collection->getTargetArtifactIds());
        self::assertEmpty($removed_collection->getTargetArtifactIds());
    }

    /**
     * @return \Generator<string, array{0:ForwardLinkStub, 1:ForwardLinkStub}>
     */
    public static function provideChangeOfType(): \Generator
    {
        yield 'Remove type' => [ForwardLinkStub::withType(280, '_is_child'), ForwardLinkStub::withNoType(280)];
        yield 'Change type' => [ForwardLinkStub::withType(89, '_is_child'), ForwardLinkStub::withType(89, 'custom')];
        yield 'Add type' => [ForwardLinkStub::withNoType(386), ForwardLinkStub::withType(386, '_is_child')];
    }

    /**
     * @dataProvider provideChangeOfType
     */
    public function testItReturnsACollectionOfLinksThatHaveChangedType(
        ForwardLinkStub $existing,
        ForwardLinkStub $submitted,
    ): void {
        $existing_links  = new CollectionOfForwardLinks([$existing]);
        $submitted_links = new CollectionOfForwardLinks([$submitted]);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->getArtifactLinks();
        self::assertCount(1, $changed_links);
        self::assertEqualsCanonicalizing([$submitted], $changed_links);
    }

    public function testItReturnsSeveralChangesOfLinkTypes(): void
    {
        $existing_links  = new CollectionOfForwardLinks($this->artifact_links);
        $submitted_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, 'custom'),
            ForwardLinkStub::withNoType(self::SECOND_ARTIFACT_ID),
            ForwardLinkStub::withType(self::THIRD_ARTIFACT_ID, '_is_child'),
        ]);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->getArtifactLinks();
        self::assertCount(3, $changed_links);
        self::assertEqualsCanonicalizing([
            ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, 'custom'),
            ForwardLinkStub::withNoType(self::SECOND_ARTIFACT_ID),
            ForwardLinkStub::withType(self::THIRD_ARTIFACT_ID, '_is_child'),
        ], $changed_links);
    }

    public function testItIgnoresAddedLinksFromTheOtherCollection(): void
    {
        $existing_links  = new CollectionOfForwardLinks([ForwardLinkStub::withNoType(105)]);
        $submitted_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(105, 'custom'),
            ForwardLinkStub::withNoType(430),
            ForwardLinkStub::withType(830, '_is_child'),
        ]);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->getTargetArtifactIds();
        self::assertCount(1, $changed_links);
    }

    public function testItReturnsAnEmptyCollectionWhenThereIsNoChange(): void
    {
        $existing_links  = new CollectionOfForwardLinks($this->artifact_links);
        $submitted_links = new CollectionOfForwardLinks($this->artifact_links);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->getTargetArtifactIds();
        self::assertEmpty($changed_links);
    }
}
