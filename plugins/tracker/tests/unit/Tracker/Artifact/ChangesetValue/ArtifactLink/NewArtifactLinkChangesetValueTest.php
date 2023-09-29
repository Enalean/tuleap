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
    private ChangeForwardLinksCommand $forward_links_command;

    protected function setUp(): void
    {
        $this->forward_links_command = ChangeForwardLinksCommand::fromParts(
            989,
            new CollectionOfForwardLinks([
                ForwardLinkStub::withNoType(5),
                ForwardLinkStub::withType(99, 'custom_type'),
            ]),
            new CollectionOfForwardLinks([]),
            new CollectionOfForwardLinks([])
        );
    }

    public function testItBuildsFromParts(): void
    {
        $new_parent_link         = Option::fromValue(NewParentLinkStub::withId(510));
        $submitted_reverse_links = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(200)]);

        $value = NewArtifactLinkChangesetValue::fromParts(
            $this->forward_links_command,
            $new_parent_link,
            $submitted_reverse_links
        );

        self::assertSame($this->forward_links_command, $value->getChangeForwardLinksCommand());
        self::assertSame($new_parent_link, $value->getNewParentLink());
        self::assertSame($submitted_reverse_links, $value->getSubmittedReverseLinks());
    }

    public function testItBuildsWithOnlyForwardLinks(): void
    {
        $value = NewArtifactLinkChangesetValue::fromOnlyForwardLinks($this->forward_links_command);

        self::assertSame($this->forward_links_command, $value->getChangeForwardLinksCommand());
        self::assertTrue($value->getNewParentLink()->isNothing());
        self::assertEmpty($value->getSubmittedReverseLinks()->links);
    }
}
