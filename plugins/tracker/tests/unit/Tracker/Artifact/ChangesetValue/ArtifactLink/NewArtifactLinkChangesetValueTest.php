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

use Tuleap\Option\Option;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class NewArtifactLinkChangesetValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 989;
    /** @var Option<CollectionOfForwardLinks> */
    private Option $submitted_links;
    private CollectionOfReverseLinks $submitted_reverse_links;
    /** @var Option<NewParentLink> */
    private Option $parent;

    protected function setUp(): void
    {
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks([
                ForwardLinkStub::withNoType(5),
                ForwardLinkStub::withType(99, 'custom_type'),
            ])
        );

        $this->submitted_reverse_links = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(200)]);
        $this->parent                  = Option::nothing(NewParentLink::class);
    }

    private function build(): NewArtifactLinkChangesetValue
    {
        $existing_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(191, '_is_child'),
            ForwardLinkStub::withNoType(274),
        ]);

        return NewArtifactLinkChangesetValue::fromParts(
            self::FIELD_ID,
            $existing_links,
            $this->submitted_links,
            $this->parent,
            $this->submitted_reverse_links
        );
    }

    public function testItBuildsADiffBetweenExistingLinksAndSubmittedLinks(): void
    {
        $value = $this->build();

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertTrue($value->getSubmittedValues()->isValue());
        self::assertSame($this->submitted_links, $value->getSubmittedValues());
        self::assertSame($this->submitted_reverse_links, $value->getSubmittedReverseLinks());
        self::assertCount(2, $value->getAddedValues()->getTargetArtifactIds());
        self::assertCount(2, $value->getRemovedValues()->getTargetArtifactIds());
    }

    public function testAddedAndRemovedValuesAreEmptyWhenSubmittedValuesAreNull(): void
    {
        $this->submitted_links = Option::nothing(CollectionOfForwardLinks::class);
        $value                 = $this->build();

        self::assertEmpty($value->getAddedValues()->getArtifactLinks());
        self::assertEmpty($value->getRemovedValues()->getArtifactLinks());
        self::assertTrue($value->getSubmittedValues()->isNothing());
    }

    public function testItBuildsWithAParent(): void
    {
        $this->parent = Option::fromValue(NewParentLinkStub::withId(3));
        $value        = $this->build();

        self::assertSame(3, $value->getParent()->unwrapOr(null)?->getParentArtifactId());
    }

    public function testItBuildsFromOnlyAddedValues(): void
    {
        $added_values = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(42, 'custom_type'),
            ForwardLinkStub::withNoType(572),
        ]);
        $value        = NewArtifactLinkChangesetValue::fromAddedAndUpdatedTypeValues(self::FIELD_ID, $added_values);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertSame($added_values, $value->getAddedValues());
        self::assertEmpty($value->getRemovedValues()->getArtifactLinks());
        self::assertSame($added_values, $value->getSubmittedValues()->unwrapOr(new CollectionOfForwardLinks([])));
        self::assertTrue($value->getParent()->isNothing());
        self::assertEmpty($value->getSubmittedReverseLinks()->links);
    }
}
