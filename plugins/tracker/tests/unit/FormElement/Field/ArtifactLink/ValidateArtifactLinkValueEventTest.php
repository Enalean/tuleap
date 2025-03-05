<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ValidateArtifactLinkValueEventTest extends TestCase
{
    private const FIRST_ARTIFACT_ID  = 4457;
    private const SECOND_ARTIFACT_ID = 5597;

    public function testItBuildsTheEventWithEmptyLinksIfNoLinksProvided(): void
    {
        $values = [];
        $event  = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            ArtifactTestBuilder::anArtifact(101)->build(),
            $values,
        );

        self::assertEmpty($event->getSubmittedLinksForDeletion());
        self::assertEmpty($event->getSubmittedLinksWithTypes());
    }

    public function testItBuildsTheEventWithOnlyUpdatedLinksIfNoLinksDeleted(): void
    {
        $values = [
            'types' => [self::FIRST_ARTIFACT_ID => '', self::SECOND_ARTIFACT_ID => '_is_child'],
        ];

        $event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            ArtifactTestBuilder::anArtifact(101)->build(),
            $values,
        );

        self::assertEmpty($event->getSubmittedLinksForDeletion());
        self::assertEqualsCanonicalizing(
            [self::FIRST_ARTIFACT_ID => '', self::SECOND_ARTIFACT_ID => '_is_child'],
            $event->getSubmittedLinksWithTypes(),
        );
    }

    public function testItBuildsTheEventWithOnlyDeletedLinks(): void
    {
        $values = [
            'removed_values' => [
                self::FIRST_ARTIFACT_ID => [self::FIRST_ARTIFACT_ID],
                self::SECOND_ARTIFACT_ID => [self::SECOND_ARTIFACT_ID],
            ],
        ];

        $event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            ArtifactTestBuilder::anArtifact(101)->build(),
            $values,
        );

        self::assertEmpty($event->getSubmittedLinksWithTypes());
        self::assertEqualsCanonicalizing(
            [self::FIRST_ARTIFACT_ID, self::SECOND_ARTIFACT_ID],
            $event->getSubmittedLinksForDeletion(),
        );
    }

    public function testItBuildsTheEventWithDeletedAndUpdatedLinks(): void
    {
        $values = [
            'removed_values' => [self::FIRST_ARTIFACT_ID => [self::FIRST_ARTIFACT_ID]],
            'types' => [self::SECOND_ARTIFACT_ID => '_is_child'],
        ];

        $event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            ArtifactTestBuilder::anArtifact(101)->build(),
            $values,
        );

        self::assertEqualsCanonicalizing(
            [self::SECOND_ARTIFACT_ID => '_is_child'],
            $event->getSubmittedLinksWithTypes(),
        );
        self::assertEqualsCanonicalizing(
            [self::FIRST_ARTIFACT_ID],
            $event->getSubmittedLinksForDeletion(),
        );
    }
}
