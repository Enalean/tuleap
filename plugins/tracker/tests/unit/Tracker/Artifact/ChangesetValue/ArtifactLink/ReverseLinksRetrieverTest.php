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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\SearchReverseLinksStub;

final class ReverseLinksRetrieverTest extends TestCase
{
    public function testItReturnsTheRetrievedNonNullReverseLinksArtifact(): void
    {
        $linked_artifact_1 = new StoredLinkRow(1, "is_child");
        $linked_artifact_2 = new StoredLinkRow(40, "");

        $search_reverse_link = SearchReverseLinksStub::withRows(
            $linked_artifact_1,
            $linked_artifact_2
        );

        $user = UserTestBuilder::buildWithDefaults();

        $artifact          = ArtifactTestBuilder::anArtifact(12)->userCanView($user)->build();
        $retrieve_artifact = RetrieveArtifactStub::withArtifacts($artifact);

        $reverse_link_retriever = new ReverseLinksRetriever(
            $search_reverse_link,
            $retrieve_artifact
        );

        $reverse_link = $reverse_link_retriever->retrieveReverseLinks(
            $artifact,
            $user
        );

        $expected_artifact          = ArtifactTestBuilder::anArtifact(12)->userCanView($user)->build();
        $expected_retrieve_artifact = RetrieveArtifactStub::withArtifacts($expected_artifact);
        $expected_reverse_links     = new CollectionOfReverseLinks([StoredReverseLink::fromRow($expected_retrieve_artifact, $user, $linked_artifact_1)]);

        self::assertEquals($expected_reverse_links, $reverse_link);
    }
}
