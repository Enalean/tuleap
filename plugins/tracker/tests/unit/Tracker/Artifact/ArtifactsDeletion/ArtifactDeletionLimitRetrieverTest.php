<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\ConfigurationArtifactsDeletionStub;
use Tuleap\Tracker\Test\Stub\RetrieveUserDeletionForLastDayStub;

final class ArtifactDeletionLimitRetrieverTest extends TestCase
{
    public function testItThrowsWhenLimitIsNotSet(): void
    {
        $deletion_limit     = ConfigurationArtifactsDeletionStub::withLimit(0);
        $user_deletion_done = RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(0);
        $retriever          = new ArtifactDeletionLimitRetriever($deletion_limit, $user_deletion_done);

        $this->expectException(DeletionOfArtifactsIsNotAllowedException::class);
        $retriever->getNumberOfArtifactsAllowedToDelete(UserTestBuilder::anActiveUser()->build());
    }

    public function testItThrowsWhenLimitIsReached(): void
    {
        $deletion_limit     = ConfigurationArtifactsDeletionStub::withLimit(10);
        $user_deletion_done = RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(10);
        $retriever          = new ArtifactDeletionLimitRetriever($deletion_limit, $user_deletion_done);

        $this->expectException(ArtifactsDeletionLimitReachedException::class);
        $retriever->getNumberOfArtifactsAllowedToDelete(UserTestBuilder::anActiveUser()->build());
    }

    public function testItReturnsNumberOfPossibleRemainingDeletion(): void
    {
        $deletion_limit     = ConfigurationArtifactsDeletionStub::withLimit(10);
        $user_deletion_done = RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(1);
        $retriever          = new ArtifactDeletionLimitRetriever($deletion_limit, $user_deletion_done);

        self::assertSame(9, $retriever->getNumberOfArtifactsAllowedToDelete(UserTestBuilder::anActiveUser()->build()));
    }
}
