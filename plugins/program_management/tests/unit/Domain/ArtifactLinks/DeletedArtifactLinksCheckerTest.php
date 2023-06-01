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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\ArtifactLinks;

use Tuleap\ProgramManagement\Tests\Stub\DeletedArtifactLinksEventStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchLinkedArtifactsStub;
use Tuleap\Test\PHPUnit\TestCase;

final class DeletedArtifactLinksCheckerTest extends TestCase
{
    private const ARTIFACT_ID        = 7821;
    private const LINKED_ARTIFACT_ID = 1115;

    public function testItDoesNothingIfThereIsNoLinkInDeletedLinks(): void
    {
        $checker = new DeletedArtifactLinksChecker(
            SearchLinkedArtifactsStub::withoutMirroredMilestones()
        );

        $deleted_links = [];
        $event         = DeletedArtifactLinksEventStub::withData(
            self::ARTIFACT_ID,
            $deleted_links
        );
        $checker->checkArtifactHaveMirroredMilestonesInProvidedDeletedLinks($event);

        self::assertSame(0, $event->getCallCount());
    }

    public function testItDoesNothingIfNoSystemLinkDeleted(): void
    {
        $checker = new DeletedArtifactLinksChecker(
            SearchLinkedArtifactsStub::withoutMirroredMilestones()
        );

        $deleted_links = [self::LINKED_ARTIFACT_ID];
        $event         = DeletedArtifactLinksEventStub::withData(
            self::ARTIFACT_ID,
            $deleted_links
        );
        $checker->checkArtifactHaveMirroredMilestonesInProvidedDeletedLinks($event);

        self::assertSame(0, $event->getCallCount());
    }

    public function testItSetAsErrorIfDeletedLinksContainsSystemLink(): void
    {
        $checker = new DeletedArtifactLinksChecker(
            SearchLinkedArtifactsStub::withMirroredMilestones()
        );

        $deleted_links = [self::LINKED_ARTIFACT_ID];
        $event         = DeletedArtifactLinksEventStub::withData(
            self::ARTIFACT_ID,
            $deleted_links
        );
        $checker->checkArtifactHaveMirroredMilestonesInProvidedDeletedLinks($event);

        self::assertSame(1, $event->getCallCount());
    }
}
