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

final class NewArtifactLinkChangesetValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 989;
    private ?CollectionOfForwardLinks $submitted_links;

    protected function setUp(): void
    {
        $this->submitted_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(5),
            ForwardLinkStub::withType(99, 'custom_type'),
        ]);
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
            $parent
        );
    }

    public function testItBuildsADiffBetweenExistingLinksAndSubmittedLinks(): void
    {
        $value = $this->build(null);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNotNull($value->getSubmittedValues());
        self::assertSame($this->submitted_links, $value->getSubmittedValues());
        $diff = $value->getArtifactLinksDiff();
        self::assertNotNull($diff);
        self::assertCount(2, $diff->getNewValues());
        self::assertCount(2, $diff->getRemovedValues());
        self::assertNull($value->getParent());
    }

    public function testItSetsDiffToNullToAvoidRemovingValuesWhenSubmittedValuesAreNull(): void
    {
        $this->submitted_links = null;
        $value                 = $this->build(null);

        self::assertNull($value->getArtifactLinksDiff());
        self::assertNull($value->getSubmittedValues());
    }

    public function testItBuildsWithAParent(): void
    {
        $value = $this->build(NewParentLinkStub::withId(3));

        self::assertNotNull($value->getParent());
        self::assertSame(3, $value->getParent()->getParentArtifactId());
    }
}
