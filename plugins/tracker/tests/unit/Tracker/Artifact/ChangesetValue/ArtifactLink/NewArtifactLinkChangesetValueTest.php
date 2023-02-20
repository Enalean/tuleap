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

use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class NewArtifactLinkChangesetValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 989;
    private ?CollectionOfForwardLinks $submitted_links;
    private CollectionOfReverseLinks $submitted_reverse_links;

    protected function setUp(): void
    {
        $this->submitted_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(5),
            ForwardLinkStub::withType(99, 'custom_type'),
        ]);

        $this->submitted_reverse_links = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(200)]);
    }

    private function build(?NewParentLink $parent): NewArtifactLinkChangesetValue
    {
        $existing_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(191, '_is_child'),
            ForwardLinkStub::withNoType(274),
        ]);

        return NewArtifactLinkChangesetValue::fromParts(
            self::FIELD_ID,
            $existing_links,
            $this->submitted_links,
            $parent,
            $this->submitted_reverse_links
        );
    }

    public function testItBuildsADiffBetweenExistingLinksAndSubmittedLinks(): void
    {
        $value = $this->build(null);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNotNull($value->getSubmittedValues());
        self::assertSame($this->submitted_links, $value->getSubmittedValues());
        self::assertSame($this->submitted_reverse_links, $value->getSubmittedReverseLinks());
        self::assertCount(2, $value->getAddedValues()->getTargetArtifactIds());
        self::assertCount(2, $value->getRemovedValues()->getTargetArtifactIds());
    }

    public function testAddedAndRemovedValuesAreEmptyWhenSubmittedValuesAreNull(): void
    {
        $this->submitted_links = null;
        $value                 = $this->build(null);

        self::assertEmpty($value->getAddedValues()->getArtifactLinks());
        self::assertEmpty($value->getRemovedValues()->getArtifactLinks());
        self::assertNull($value->getSubmittedValues());
    }

    public function testItBuildsWithAParent(): void
    {
        $value = $this->build(NewParentLinkStub::withId(3));

        self::assertNotNull($value->getParent());
        self::assertSame(3, $value->getParent()->getParentArtifactId());
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
        self::assertSame($added_values, $value->getSubmittedValues());
        self::assertNull($value->getParent());
        self::assertEmpty($value->getSubmittedReverseLinks()->links);
    }
}
