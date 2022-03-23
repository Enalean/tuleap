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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use Tuleap\Tracker\Test\Stub\LinkStub;

final class ArtifactLinksDiffTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsADiffOfAddedAndRemovedLinks(): void
    {
        $current_forward_links = new CollectionOfArtifactLinks([
            LinkStub::withType(101, '_is_child'),
            LinkStub::withNoType(102),
            LinkStub::withNoType(103),
        ]);

        $submitted_links = new CollectionOfArtifactLinks([
            LinkStub::withType(101, '_is_child'),
            LinkStub::withType(102, '_is_child'),
            LinkStub::withType(104, '_is_child'),
        ]);

        $diff = ArtifactLinksDiff::build($submitted_links, $current_forward_links);

        $new_values = $diff->getNewValues();
        self::assertCount(1, $new_values);
        self::assertContains(104, $new_values);
        $removed_values = $diff->getRemovedValues();
        self::assertCount(1, $removed_values);
        self::assertContains(103, $removed_values);
    }

    public function testItBuildsAnEmptyDiffWhenThereIsNoChange(): void
    {
        $submitted_links       = new CollectionOfArtifactLinks([LinkStub::withType(101, '_is_child')]);
        $current_forward_links = new CollectionOfArtifactLinks([LinkStub::withType(101, '_is_child')]);

        $diff = ArtifactLinksDiff::build($submitted_links, $current_forward_links);

        self::assertEmpty($diff->getNewValues());
        self::assertEmpty($diff->getRemovedValues());
    }
}
