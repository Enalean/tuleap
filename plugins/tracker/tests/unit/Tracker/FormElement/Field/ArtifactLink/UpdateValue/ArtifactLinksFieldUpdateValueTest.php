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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use Tuleap\Tracker\Test\Stub\LinkStub;

final class ArtifactLinksFieldUpdateValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ?CollectionOfArtifactLinks $submitted_links;

    protected function setUp(): void
    {
        $this->submitted_links = new CollectionOfArtifactLinks([
            LinkStub::withNoType(5),
            LinkStub::withType(99, 'custom_type'),
        ]);
    }

    private function build(?Link $parent): ArtifactLinksFieldUpdateValue
    {
        $existing_links = new CollectionOfArtifactLinks([
            LinkStub::withType(191, '_is_child'),
            LinkStub::withNoType(274),
        ]);

        return ArtifactLinksFieldUpdateValue::build($existing_links, $this->submitted_links, $parent);
    }

    public function testItBuildsADiffBetweenExistingLinksAndSubmittedLinks(): void
    {
        $value = $this->build(null);

        self::assertNotNull($value->getSubmittedValues());
        self::assertSame($this->submitted_links, $value->getSubmittedValues());
        $diff = $value->getArtifactLinksDiff();
        self::assertNotNull($diff);
        self::assertCount(2, $diff->getNewValues());
        self::assertCount(2, $diff->getRemovedValues());
        self::assertNull($value->getParentArtifactLink());
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
        $value = $this->build(LinkStub::withNoType(3));

        self::assertNotNull($value->getParentArtifactLink());
        self::assertSame(3, $value->getParentArtifactLink()->getTargetArtifactId());
    }
}
