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

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class CollectionOfReverseLinksTest extends TestCase
{
    private const ARTIFACT_ID_1 = 15;
    private const FIRST_TYPE    = '_is_child';
    private const ARTIFACT_ID_2 = 20;
    private const SECOND_TYPE   = 'wololo';
    private const ARTIFACT_ID_3 = 100;
    /** @var ReverseLinkStub[] */
    private array $links;

    protected function setUp(): void
    {
        $this->links = [
            ReverseLinkStub::withType(self::ARTIFACT_ID_1, self::FIRST_TYPE),
            ReverseLinkStub::withType(self::ARTIFACT_ID_2, self::SECOND_TYPE),
            ReverseLinkStub::withNoType(self::ARTIFACT_ID_3),
        ];
    }

    public function testItReturnsTheDiffByArtifactIdOfTwoCollections(): void
    {
        $current_links   = new CollectionOfReverseLinks($this->links);
        $submitted_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(self::ARTIFACT_ID_1, self::FIRST_TYPE),
            ReverseLinkStub::withType(self::ARTIFACT_ID_2, '_is_child'),
            ReverseLinkStub::withNoType(969),
        ]);

        $added_links = $current_links->differenceById($submitted_links)->links;
        self::assertCount(1, $added_links);
        self::assertSame(969, $added_links[0]->getSourceArtifactId());
        $removed_links = $submitted_links->differenceById($current_links)->links;
        self::assertCount(1, $removed_links);
        self::assertSame(self::ARTIFACT_ID_3, $removed_links[0]->getSourceArtifactId());
    }

    public function testItReturnsAnEmptyDiffWhenThereIsNoChange(): void
    {
        $current_links   = new CollectionOfReverseLinks($this->links);
        $submitted_links = new CollectionOfReverseLinks($this->links);

        $added_links   = $current_links->differenceById($submitted_links);
        $removed_links = $submitted_links->differenceById($current_links);

        self::assertEmpty($added_links->links);
        self::assertEmpty($removed_links->links);
    }

    /**
     * @return \Generator<string, array{0:ReverseLinkStub, 1:ReverseLinkStub}>
     */
    public static function provideChangeOfType(): \Generator
    {
        yield 'Remove type' => [ReverseLinkStub::withType(93, '_is_child'), ReverseLinkStub::withNoType(93)];
        yield 'Change type' => [ReverseLinkStub::withType(366, '_is_child'), ReverseLinkStub::withType(366, 'custom')];
        yield 'Add type' => [ReverseLinkStub::withNoType(643), ReverseLinkStub::withType(643, '_is_child')];
    }

    /**
     * @dataProvider provideChangeOfType
     */
    public function testItReturnsACollectionOfLinksThatHaveChangedType(
        ReverseLinkStub $existing,
        ReverseLinkStub $submitted,
    ): void {
        $existing_links  = new CollectionOfReverseLinks([$existing]);
        $submitted_links = new CollectionOfReverseLinks([$submitted]);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->links;
        self::assertCount(1, $changed_links);
        self::assertEqualsCanonicalizing([$submitted], $changed_links);
    }

    public function testItReturnsSeveralChangesOfLinkTypes(): void
    {
        $existing_links  = new CollectionOfReverseLinks($this->links);
        $submitted_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(self::ARTIFACT_ID_1, 'custom'),
            ReverseLinkStub::withNoType(self::ARTIFACT_ID_2),
            ReverseLinkStub::withType(self::ARTIFACT_ID_3, '_is_child'),
        ]);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->links;
        self::assertCount(3, $changed_links);
        self::assertEqualsCanonicalizing([
            ReverseLinkStub::withType(self::ARTIFACT_ID_1, 'custom'),
            ReverseLinkStub::withNoType(self::ARTIFACT_ID_2),
            ReverseLinkStub::withType(self::ARTIFACT_ID_3, '_is_child'),
        ], $changed_links);
    }

    public function testItIgnoresAddedLinksFromTheOtherCollection(): void
    {
        $existing_links  = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(105)]);
        $submitted_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(105, 'custom'),
            ReverseLinkStub::withNoType(430),
            ReverseLinkStub::withType(830, '_is_child'),
        ]);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->links;
        self::assertCount(1, $changed_links);
    }

    public function testItReturnsAnEmptyCollectionWhenThereIsNoChange(): void
    {
        $existing_links  = new CollectionOfReverseLinks($this->links);
        $submitted_links = new CollectionOfReverseLinks($this->links);

        $changed_links = $existing_links->getLinksThatHaveChangedType($submitted_links)->links;
        self::assertEmpty($changed_links);
    }
}
