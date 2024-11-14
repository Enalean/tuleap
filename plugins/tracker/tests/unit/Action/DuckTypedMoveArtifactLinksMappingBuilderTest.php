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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Action;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveAnArtifactLinkFieldStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;

final class DuckTypedMoveArtifactLinksMappingBuilderTest extends TestCase
{
    public function testItReturnsAnEmptyMappingWhenTheSourceTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(101)->build();
        $user           = UserTestBuilder::anActiveUser()->build();

        $builder = new DuckTypedMoveArtifactLinksMappingBuilder(
            RetrieveAnArtifactLinkFieldStub::withoutAnArtifactLinkField(),
            RetrieveForwardLinksStub::withoutLinks()
        );

        $mapping = $builder->buildMapping(
            $source_tracker,
            $artifact,
            $user
        );

        self::assertEmpty($mapping->getMapping());
    }

    public function testItReturnsAMappingContainingAllTheLinkedArtifacts(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(101)->build();
        $user           = UserTestBuilder::anActiveUser()->build();

        $forward_link_to_artifact_1 = ForwardLinkStub::withType(1, 'custom');
        $forward_link_to_artifact_2 = ForwardLinkStub::withType(2, 'system');

        $builder = new DuckTypedMoveArtifactLinksMappingBuilder(
            RetrieveAnArtifactLinkFieldStub::withAnArtifactLinkField(
                ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build()
            ),
            RetrieveForwardLinksStub::withLinks(
                new CollectionOfForwardLinks([
                    $forward_link_to_artifact_1,
                    $forward_link_to_artifact_2,
                ])
            )
        );

        $mapping = $builder->buildMapping(
            $source_tracker,
            $artifact,
            $user
        );

        self::assertCount(2, $mapping->getMapping());
        self::assertSame(
            $mapping->get($forward_link_to_artifact_1->getTargetArtifactId()),
            $forward_link_to_artifact_1->getTargetArtifactId()
        );
        self::assertSame(
            $mapping->get($forward_link_to_artifact_2->getTargetArtifactId()),
            $forward_link_to_artifact_2->getTargetArtifactId()
        );
    }
}
